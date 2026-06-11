<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_note_presets', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->unique();
            $table->timestamps();
        });

        DB::table('resolution_note_presets')->insert([
            ['label' => 'Charge Too Small',   'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Not Delivered Full', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Prepaid Fuel',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_note_presets');
    }
};
