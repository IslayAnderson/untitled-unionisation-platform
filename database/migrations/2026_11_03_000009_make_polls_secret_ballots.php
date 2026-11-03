<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polls are secret ballots: poll_voters records who has voted, and the
     * answers' vote_count totals record what was chosen, with nothing linking
     * the two. Neither has timestamps, so the timing of a vote can't link them
     * either. Replaces poll_submissions, which stored each member's answer.
     */
    public function up(): void
    {
        Schema::dropIfExists('poll_submissions');

        Schema::table('polls', function (Blueprint $table) {
            $table->boolean('allows_multiple')->default(false)->after('description');
            $table->timestamp('closes_at')->nullable()->after('allows_multiple');
            $table->timestamp('closed_at')->nullable()->after('closes_at');
            $table->unsignedInteger('ballot_count')->default(0)->after('closed_at');
        });

        Schema::table('poll_answers', function (Blueprint $table) {
            $table->unsignedSmallInteger('position')->default(0)->after('poll_id');
            $table->unsignedInteger('vote_count')->default(0)->after('description');
        });

        Schema::create('poll_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->unique(['poll_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_voters');

        Schema::table('poll_answers', function (Blueprint $table) {
            $table->dropColumn(['position', 'vote_count']);
        });

        Schema::table('polls', function (Blueprint $table) {
            $table->dropColumn(['allows_multiple', 'closes_at', 'closed_at', 'ballot_count']);
        });

        Schema::create('poll_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['poll_id', 'contact_id']);
        });
    }
};
