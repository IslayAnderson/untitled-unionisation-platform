<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row each time a QR code is opened, for the admin area's counts. It
     * records which code and when, never who scanned it. See docs/architecture/admin.md.
     */
    public function up(): void
    {
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 16)->index();
            $table->dateTime('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scans');
    }
};
