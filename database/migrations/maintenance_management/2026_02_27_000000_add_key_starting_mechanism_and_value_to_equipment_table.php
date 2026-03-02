<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->after('date_acquired', function (Blueprint $table) {
                $table->string('key_starting_mechanism')->nullable();
                $table->decimal('equipment_value', 15, 2)->nullable();
            });
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['key_starting_mechanism', 'equipment_value']);
        });
    }
};
