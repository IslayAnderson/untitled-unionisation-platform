<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files sent with union mail. Images point at the compressed copy in
     * uploaded_images; documents are stored privately as they were uploaded.
     */
    public function up(): void
    {
        Schema::create('union_mail_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('union_mail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_image_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('name');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('union_mail_attachments');
    }
};
