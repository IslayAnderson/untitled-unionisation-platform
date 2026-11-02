<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['building_id', 'title', 'rich_text', 'rules', 'opened_at', 'published_at'])]
class Constitution extends Model
{
    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'opened_at' => 'datetime',
            'published_at' => 'datetime',
        ];
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
