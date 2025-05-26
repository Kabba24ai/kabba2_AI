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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->string('unique_id')->unique();
            $table->enum('asset_type', ['Public Asset', 'Secure Asset'])->default('Public Asset');
            // Laravel polymorphic relation
            $table->nullableMorphs('model'); // model_type, model_id

            // File and media details
            $table->string('folder_name')->nullable(); // media/images, media/videos
            $table->string('file_name')->unique();               // unique filename stored
            $table->string('original_file_name')->nullable();
            $table->string('file_extension')->nullable();
            $table->string('file_type')->nullable();   // semantic: image, doc, etc.
            $table->string('mime_type')->nullable();   // MIME type like image/png
            $table->double('file_size')->nullable(); // in bytes

            // Security & access control
            $table->string('signed_url')->nullable(); // optional (persisted/cached)
            $table->string('download_signed_url')->nullable(); // optional

            $table->enum('is_used', ['Yes', 'No'])->default('Yes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
