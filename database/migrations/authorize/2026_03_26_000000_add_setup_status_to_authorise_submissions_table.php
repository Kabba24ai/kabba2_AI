<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authorise_submissions', function (Blueprint $table) {
            $table->enum('setup_status', ['pending', 'in_progress', 'completed'])
                ->default('pending')
                ->after('status');
            $table->text('comment')->nullable()->after('setup_status');
        });
    }

    public function down(): void
    {
        Schema::table('authorise_submissions', function (Blueprint $table) {
            $table->dropColumn('setup_status');
            $table->dropColumn('comment');
        });
    }
};
