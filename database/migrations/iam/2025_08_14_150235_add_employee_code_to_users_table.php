<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add column nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 6)->nullable()->after('unique_id');
            $table->dropColumn('clock_code');
        });

        // Step 2: Fill existing rows only if users exist
        if (DB::table('users')->exists()) {
            DB::table('users')
                ->whereNull('employee_code')
                ->orderBy('id')
                ->chunk(100, function ($users) {
                    foreach ($users as $user) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'employee_code' => self::generateSixDigitCode(),
                            ]);
                    }
                });
        }

        // Step 3: Make it unique & NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 6)->nullable(false)->unique()->change();
        });
    }

    private static function generateSixDigitCode(): string
    {
        do {
            $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (DB::table('users')->where('employee_code', $code)->exists());

        return $code;
    }
};
