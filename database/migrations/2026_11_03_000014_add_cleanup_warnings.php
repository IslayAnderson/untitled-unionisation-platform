<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clean-up warns account holders first, and only deletes if they don't
     * come back within a week. See docs/architecture/admin.md.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('is_admin');
            $table->timestamp('cleanup_warned_at')->nullable()->after('last_seen_at');
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->timestamp('cleanup_warned_at')->nullable()->after('current_constitution_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'cleanup_warned_at']);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('cleanup_warned_at');
        });
    }
};
