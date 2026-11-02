<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mailing lists, bulletins, polls, threads and replies.
     */
    public function up(): void
    {
        Schema::create('mailing_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->nullable()->constrained('contact_types')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('landlord_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('contact_mailing_list', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['contact_id', 'mailing_list_id']);
        });

        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('title');
            $table->longText('rich_text');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('poll_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('poll_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['poll_id', 'contact_id']);
        });

        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('title');
            $table->longText('rich_text');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('rich_text');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replies');
        Schema::dropIfExists('threads');
        Schema::dropIfExists('poll_submissions');
        Schema::dropIfExists('poll_answers');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('bulletins');
        Schema::dropIfExists('contact_mailing_list');
        Schema::dropIfExists('mailing_lists');
    }
};
