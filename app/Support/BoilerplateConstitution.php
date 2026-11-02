<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Constitution;

/**
 * The constitution every new union starts with. Unions amend it by referendum.
 */
final class BoilerplateConstitution
{
    public static function createFor(Building $building): Constitution
    {
        $rules = ConstitutionRules::fromArray([]);

        $constitution = $building->constitutions()->create([
            'title' => "Constitution of {$building->name}",
            'rich_text' => view('constitutions.boilerplate', ['building' => $building, 'rules' => $rules])->render(),
            'rules' => $rules->toArray(),
            'opened_at' => now(),
            'published_at' => now(),
        ]);

        $building->update(['current_constitution_id' => $constitution->id]);

        return $constitution;
    }
}
