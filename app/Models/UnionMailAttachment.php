<?php

namespace App\Models;

use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Mail\Attachment;

/**
 * A file sent with union mail. See docs/architecture/mail.md.
 */
#[Fillable(['union_mail_id', 'uploaded_image_id', 'disk', 'path', 'name', 'mime_type', 'size'])]
class UnionMailAttachment extends Model implements Attachable
{
    protected function casts(): array
    {
        return [
            'union_mail_id' => 'integer',
            'uploaded_image_id' => 'integer',
            'size' => 'integer',
        ];
    }

    public function toMailAttachment(): Attachment
    {
        return Attachment::fromStorageDisk($this->disk, $this->path)
            ->as($this->name)
            ->withMime($this->mime_type);
    }

    /**
     * @return BelongsTo<UnionMail, $this>
     */
    public function unionMail(): BelongsTo
    {
        return $this->belongsTo(UnionMail::class);
    }

    /**
     * The compressed image this was made from, if it's an image.
     *
     * @return BelongsTo<UploadedImage, $this>
     */
    public function uploadedImage(): BelongsTo
    {
        return $this->belongsTo(UploadedImage::class);
    }
}
