<?php

namespace App\Models;

use Database\Factories\UploadedImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An image a member uploaded into rich text, compressed and stripped of
 * metadata. See docs/architecture/uploads.md.
 */
#[Fillable([
    'building_id', 'uploader_contact_id', 'disk', 'path', 'mime_type', 'width', 'height',
    'size', 'original_size', 'original_name', 'cache_warmed_at',
])]
class UploadedImage extends Model
{
    /** @use HasFactory<UploadedImageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'building_id' => 'integer',
            'uploader_contact_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
            'original_size' => 'integer',
            'cache_warmed_at' => 'datetime',
        ];
    }

    /**
     * The public URL, served through Cloudflare in production.
     */
    public function url(): string
    {
        return url(Storage::disk($this->disk)->url($this->path));
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'uploader_contact_id');
    }
}
