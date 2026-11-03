<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles are created, filled and abolished by referendum, like policies.
     * See docs/architecture/governance.md.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('description');
            $table->timestamp('published_at')->nullable()->after('opened_at');
            $table->timestamp('abolished_at')->nullable()->after('published_at');
        });

        Schema::table('referendums', function (Blueprint $table) {
            // The member to elect to, or remove from, a role.
            $table->foreignId('candidate_id')->nullable()->after('proposer_id')->constrained('contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referendums', function (Blueprint $table) {
            $table->dropConstrainedForeignId('candidate_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['opened_at', 'published_at', 'abolished_at']);
        });
    }
};
