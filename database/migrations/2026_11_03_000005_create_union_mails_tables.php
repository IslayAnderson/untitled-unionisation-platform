<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Union mail needs approval before it is sent (docs/architecture/mail.md),
     * replacing the direct-send log.
     */
    public function up(): void
    {
        Schema::dropIfExists('mailing_list_messages');

        Schema::create('union_mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailing_list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('bulletin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 16);
            $table->string('status', 16)->index();
            $table->string('subject');
            $table->text('body');
            $table->unsignedInteger('approvals_required');
            $table->timestamp('expires_at')->index();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedInteger('recipient_count')->nullable();
            $table->timestamps();
        });

        Schema::create('union_mail_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('union_mail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('decision', 16)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['union_mail_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('union_mail_approvers');
        Schema::dropIfExists('union_mails');

        Schema::create('mailing_list_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->unsignedInteger('recipient_count');
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }
};
