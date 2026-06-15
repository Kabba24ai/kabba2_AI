<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_note_presets', function (Blueprint $table) {
            $table->id();
            $table->string('label', 255)->unique();
            $table->timestamps();
        });

        DB::table('fuel_note_presets')->insert([
            ['label' => 'Called and left a VM',                                                              'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Called and left a VM and then sent a text',                                         'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Customer denies responsibility',                                                     'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Spoke with customer about payment because the credit / debit card failed',           'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_note_presets');
    }
};
