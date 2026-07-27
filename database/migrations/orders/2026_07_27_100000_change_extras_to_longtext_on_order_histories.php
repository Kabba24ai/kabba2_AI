<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->longText('extras')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->text('extras')->nullable()->change();
        });
    }
};
