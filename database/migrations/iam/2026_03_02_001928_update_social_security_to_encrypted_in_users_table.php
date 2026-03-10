<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        // Change column to text
        Schema::table('users', function (Blueprint $table) {
            $table->text('social_security')->nullable()->change();
        });

        //  Encrypt existing non-null values
        DB::table('users')
            ->whereNotNull('social_security')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        // Try decrypting to check if already encrypted
                        Crypt::decryptString($user->social_security);
                        // If decrypt works → already encrypted → skip
                    } catch (\Exception $e) {
                        // Not encrypted → encrypt now
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'social_security' => Crypt::encryptString($user->social_security)
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        //  Decrypt values back (optional)
        DB::table('users')
            ->whereNotNull('social_security')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        $decrypted = Crypt::decryptString($user->social_security);

                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'social_security' => $decrypted
                            ]);
                    } catch (\Exception $e) {
                        // already plain → skip
                    }
                }
            });

        //  Change column back to string
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_security', 255)->nullable()->change();
        });
    }
};