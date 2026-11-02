<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Landlords, buildings/unions, constitutions, units and contacts.
     *
     * These reference each other in cycles (buildings <-> constitutions,
     * landlords <-> contacts), so the closing foreign keys are added last.
     */
    public function up(): void
    {
        Schema::create('landlord_types', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->timestamps();
        });

        Schema::create('contact_types', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->timestamps();
        });

        Schema::create('landlords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->nullable()->constrained('landlord_types')->nullOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('primary_contact_id')->nullable()->index();
            $table->string('company_number')->nullable();
            $table->timestamps();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('postcode', 16)->nullable()->index();
            $table->string('address_provider', 32);
            $table->string('address_provider_id');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('signup_token', 64)->unique();
            $table->foreignId('building_owner_id')->nullable()->constrained('landlords')->nullOnDelete();
            $table->foreignId('current_constitution_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['address_provider', 'address_provider_id']);
        });

        Schema::create('constitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('rich_text');
            $table->json('rules');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->foreign('current_constitution_id')->references('id')->on('constitutions')->nullOnDelete();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalised_name');
            $table->timestamps();

            $table->unique(['building_id', 'normalised_name']);
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->foreignId('type_id')->nullable()->constrained('contact_types')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->foreignId('landlord_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->json('pending_signup')->nullable();
            $table->string('vouch_token', 64)->unique();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });

        Schema::table('landlords', function (Blueprint $table) {
            $table->foreign('primary_contact_id')->references('id')->on('contacts')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
        });

        $now = now();

        DB::table('landlord_types')->insert(array_map(
            fn (string $type) => ['type' => $type, 'created_at' => $now, 'updated_at' => $now],
            ['Individual', 'Company', 'Housing association', 'Local authority'],
        ));

        DB::table('contact_types')->insert(array_map(
            fn (string $type) => ['type' => $type, 'created_at' => $now, 'updated_at' => $now],
            ['Tenant', 'Landlord', 'Letting agent', 'Building management'],
        ));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['contact_id']));
        Schema::table('landlords', fn (Blueprint $table) => $table->dropForeign(['primary_contact_id']));
        Schema::table('buildings', fn (Blueprint $table) => $table->dropForeign(['current_constitution_id']));

        Schema::dropIfExists('contacts');
        Schema::dropIfExists('units');
        Schema::dropIfExists('constitutions');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('landlords');
        Schema::dropIfExists('contact_types');
        Schema::dropIfExists('landlord_types');
    }
};
