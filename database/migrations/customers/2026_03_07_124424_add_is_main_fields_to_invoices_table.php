<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->enum('is_mail', ['yes', 'no'])
                ->default('no')
                ->after('is_email_send');

            $table->timestamp('is_mail_date')
                ->nullable()
                ->after('is_mail');

        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->dropColumn(['is_mail', 'is_mail_date']);

        });
    }
};
