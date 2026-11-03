<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bulletins and union mail become HTML, union mail gets a reply address
     * token, and replies can belong to a bulletin as well as a thread.
     */
    public function up(): void
    {
        Schema::table('union_mails', function (Blueprint $table) {
            $table->string('reply_token', 32)->nullable()->unique()->after('bulletin_id');
        });

        // Nothing writes replies yet, so rebuild the table rather than alter it.
        Schema::dropIfExists('replies');

        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->morphs('replyable');
            $table->foreignId('author_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('union_mail_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('message_id')->nullable()->unique();
            $table->string('title')->nullable();
            $table->longText('rich_text');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        $this->convertPlainText('bulletins', 'rich_text');
        $this->convertPlainText('union_mails', 'body');
    }

    public function down(): void
    {
        Schema::dropIfExists('replies');

        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('rich_text');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::table('union_mails', function (Blueprint $table) {
            $table->dropUnique(['reply_token']);
            $table->dropColumn('reply_token');
        });
    }

    /**
     * Wrap existing plain text in escaped paragraphs.
     */
    private function convertPlainText(string $table, string $column): void
    {
        DB::table($table)->where($column, 'not like', '<%')->lazyById()->each(function (object $row) use ($table, $column) {
            $paragraphs = preg_split('/\R{2,}/', trim($row->{$column}));
            $html = collect($paragraphs)
                ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
                ->implode('');

            DB::table($table)->where('id', $row->id)->update([$column => $html]);
        });
    }
};
