<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referendums adopt (or repeal) a policy or adopt a new version of the
     * constitution. Like polls they're secret ballots: referendum_voters records
     * who voted and the referendum holds anonymous totals. See docs/architecture/governance.md.
     */
    public function up(): void
    {
        Schema::dropIfExists('referendum_submissions');

        Schema::table('referendums', function (Blueprint $table) {
            $table->foreignId('building_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposer_id')->nullable()->after('building_id')->constrained('contacts')->nullOnDelete();
            $table->foreignId('thread_id')->nullable()->after('proposer_id')->constrained()->nullOnDelete();
            $table->string('kind')->default('adopt')->after('referendable_id');
            $table->unsignedTinyInteger('quorum_percent')->after('description');
            $table->unsignedTinyInteger('pass_percent')->after('quorum_percent');
            $table->timestamp('closes_at')->after('pass_percent');
            $table->timestamp('closed_at')->nullable()->after('closes_at');
            $table->string('outcome')->nullable()->after('closed_at');
            $table->unsignedInteger('member_count')->nullable()->after('outcome');
            $table->unsignedInteger('for_count')->default(0)->after('member_count');
            $table->unsignedInteger('against_count')->default(0)->after('for_count');
            $table->unsignedInteger('abstain_count')->default(0)->after('against_count');
            $table->unsignedInteger('ballot_count')->default(0)->after('abstain_count');
        });

        Schema::create('referendum_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referendum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->unique(['referendum_id', 'contact_id']);
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->foreignId('replaces_policy_id')->nullable()->after('building_id')->constrained('policies')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable()->after('published_at');
            $table->timestamp('repealed_at')->nullable()->after('superseded_at');
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_policy_id');
            $table->dropColumn(['superseded_at', 'repealed_at']);
        });

        Schema::dropIfExists('referendum_voters');

        Schema::table('referendums', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
            $table->dropConstrainedForeignId('proposer_id');
            $table->dropConstrainedForeignId('thread_id');
            $table->dropColumn([
                'kind', 'quorum_percent', 'pass_percent', 'closes_at', 'closed_at', 'outcome', 'member_count',
                'for_count', 'against_count', 'abstain_count', 'ballot_count',
            ]);
        });

        Schema::create('referendum_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referendum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->enum('answer', ['for', 'against', 'abstain']);
            $table->timestamps();

            $table->unique(['referendum_id', 'contact_id']);
        });
    }
};
