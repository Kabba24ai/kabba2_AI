<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('title')->nullable()->after('original_file_name');
            $table->string('alt_text', 500)->nullable()->after('title');
            $table->string('caption')->nullable()->after('alt_text');
            $table->text('description')->nullable()->after('caption');
            $table->unsignedInteger('width')->nullable()->after('description');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->unsignedBigInteger('media_folder_id')->nullable()->after('height');
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('media_folder_id');

            $table->index('media_folder_id');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['media_folder_id']);
            $table->dropColumn(['title', 'alt_text', 'caption', 'description', 'width', 'height', 'media_folder_id', 'uploaded_by']);
        });
    }
};
