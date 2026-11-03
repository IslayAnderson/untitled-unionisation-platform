<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Images members upload into rich text. Kept as a record of who uploaded
     * what, including if Cloudflare's CSAM scanning blocks one.
     */
    public function up(): void
    {
        Schema::create('uploaded_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('disk');
            $table->string('path')->unique();
            $table->string('mime_type');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size');
            $table->unsignedInteger('original_size');
            $table->string('original_name')->nullable();
            $table->timestamp('cache_warmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_images');
    }
};
