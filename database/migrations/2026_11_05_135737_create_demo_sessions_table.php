<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row each time someone enters the demo, for the admin area's count. It records
     * when, never who. Not tied to the demo union, which is rebuilt every night. See
     * docs/architecture/admin.md.
     */
    public function up(): void
    {
        Schema::create('demo_sessions', function (Blueprint $table) {
            $table->id();
            $table->dateTime('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_sessions');
    }
};
