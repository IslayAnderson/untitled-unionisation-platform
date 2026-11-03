<?php

use App\Models\Building;
use App\Support\VouchingBulletin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pinned bulletins, and a "system" key for bulletins the platform creates,
     * such as each union's vouching bulletin. Existing unions get theirs here.
     */
    public function up(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('published_at');
            $table->string('system_key', 32)->nullable()->after('pinned_at');

            $table->unique(['building_id', 'system_key']);
        });

        Building::query()->each(fn (Building $building) => VouchingBulletin::createFor($building));
    }

    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->dropUnique(['building_id', 'system_key']);
            $table->dropColumn(['pinned_at', 'system_key']);
        });
    }
};
