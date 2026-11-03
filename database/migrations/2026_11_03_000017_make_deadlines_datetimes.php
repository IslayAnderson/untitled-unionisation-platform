<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store deadlines as datetimes rather than timestamps. Where MySQL or MariaDB
     * has explicit_defaults_for_timestamp turned off, a table's first required
     * timestamp is reset to the current time whenever its row changes, so each
     * vote would move a referendum's closing time to the moment of the vote.
     */
    public function up(): void
    {
        Schema::table('referendums', function (Blueprint $table) {
            $table->dateTime('closes_at')->change();
        });

        Schema::table('union_mails', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('referendums', function (Blueprint $table) {
            $table->timestamp('closes_at')->change();
        });

        Schema::table('union_mails', function (Blueprint $table) {
            $table->timestamp('expires_at')->change();
        });
    }
};
