<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
             $table
                ->enum('note_type', ['regular', 'dashboard'])
                ->default('regular')
                ->after('note'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
            $table->dropColumn('note_type');
        });
    }
};
