<?php

namespace App\Models;

use App\Support\ConstitutionRules;
use App\Support\ConstitutionText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A version of a union's constitution. A new version is proposed by referendum
 * and becomes the building's current one if it passes.
 */
#[Fillable(['building_id', 'title', 'rich_text', 'rules', 'opened_at', 'published_at'])]
class Constitution extends Model
{
    protected function casts(): array
    {
        return [
            'building_id' => 'integer',
            'rules' => 'array',
            'opened_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function parsedRules(): ConstitutionRules
    {
        return ConstitutionRules::fromArray($this->rules ?? []);
    }

    /**
     * The text with its placeholders replaced by this version's numbers.
     */
    public function renderedText(): string
    {
        return ConstitutionText::render($this->rich_text, $this->parsedRules());
    }

    /**
     * The version that was in force when this one was proposed.
     */
    public function previousVersion(): ?self
    {
        return self::where('building_id', $this->building_id)
            ->whereKeyNot($this->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $this->opened_at ?? $this->created_at)
            ->latest('published_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return MorphMany<Referendum, $this>
     */
    public function referendums(): MorphMany
    {
        return $this->morphMany(Referendum::class, 'referendable');
    }
}
