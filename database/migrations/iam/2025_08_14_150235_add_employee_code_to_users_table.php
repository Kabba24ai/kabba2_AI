<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Iam\Personnel\User;

return new class extends Migration
{
   public function up(): void
    {
        // Step 1: Add column nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 6)->nullable()->after('unique_id');
            $table->dropColumn('clock_code');
        });

        // Step 2: Fill existing rows with unique 6-digit codes
        $users = User::all();
        foreach ($users as $user) {
            $user->employee_code = self::generateSixDigitCode();
            $user->save();
        }

        // Step 3: Make it unique & NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 6)->nullable(false)->unique()->change();
        });
    }

    /**
     * Generate a unique 6-digit numeric employee code (e.g., 365985)
     */
    private static function generateSixDigitCode()
    {
        do {
            // Always produce 6 digits (padded with leading zeros if needed)
            $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (User::where('employee_code', $code)->exists());

        return $code;
    }

};
