<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles, policies, referendums and tenant vouching.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_role', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['contact_id', 'role_id']);
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('rich_text');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referendums', function (Blueprint $table) {
            $table->id();
            $table->morphs('referendable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('referendum_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referendum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->enum('answer', ['for', 'against', 'abstain']);
            $table->timestamps();

            $table->unique(['referendum_id', 'contact_id']);
        });

        Schema::create('vouches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('vouchee_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['voucher_contact_id', 'vouchee_contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouches');
        Schema::dropIfExists('referendum_submissions');
        Schema::dropIfExists('referendums');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('contact_role');
        Schema::dropIfExists('role_actions');
        Schema::dropIfExists('roles');
    }
};
