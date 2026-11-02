<?php

namespace App\Models;

use App\Enums\ReferendumAnswer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['referendum_id', 'contact_id', 'answer'])]
class ReferendumSubmission extends Model
{
    protected function casts(): array
    {
        return [
            'answer' => ReferendumAnswer::class,
        ];
    }

    /**
     * @return BelongsTo<Referendum, $this>
     */
    public function referendum(): BelongsTo
    {
        return $this->belongsTo(Referendum::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
