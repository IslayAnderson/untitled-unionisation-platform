<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Landlords become private to each union, unions get a reply-to address,
     * and emails sent to mailing lists are logged.
     */
    public function up(): void
    {
        Schema::table('landlords', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('name');
        });

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

    public function down(): void
    {
        Schema::dropIfExists('mailing_list_messages');

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('contact_email');
        });

        Schema::table('landlords', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_id');
        });
    }
};
