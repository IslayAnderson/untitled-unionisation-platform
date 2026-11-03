<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The public demo union (php artisan demo:reset). It's read-only and
     * nobody can join it. See docs/architecture/demo.md.
     */
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('cleanup_warned_at');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
