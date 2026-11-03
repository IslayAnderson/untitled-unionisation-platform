<?php

namespace App\Services;

use App\Exceptions\ImageRejected;
use App\Models\Contact;
use App\Models\UnionMail;
use App\Models\UnionMailAttachment;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Files the author sends with union mail. Images are compressed and stripped
 * of metadata like any other upload (so they're CSAM-scanned too); documents
 * are kept privately, exactly as uploaded.
 */
class MailAttachments
{
    private const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(private ImageUploads $images) {}

    /**
     * Validation rules for the attachments[] field.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $config = config('uploads.attachments');

        return [
            'attachments' => ['nullable', 'array', 'max:'.$config['max_files'], function (string $attribute, mixed $files, Closure $fail) use ($config) {
                $total = collect($files)->sum(fn ($file) => $file instanceof UploadedFile ? $file->getSize() : 0);

                if ($total > $config['max_total_kb'] * 1024) {
                    $fail('The selected files must be smaller than '.($config['max_total_kb'] / 1024).' MB in total');
                }
            }],
            'attachments.*' => ['file', 'max:'.$config['max_file_kb'], 'mimes:'.implode(',', [...$config['document_types'], ...self::IMAGE_TYPES])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        $config = config('uploads.attachments');

        return [
            'attachments.max' => 'Select no more than '.$config['max_files'].' files',
            'attachments.*.max' => 'Each file must be smaller than '.($config['max_file_kb'] / 1024).' MB',
            'attachments.*.mimes' => 'Each file must be a PDF, document, spreadsheet, text file or image',
            'attachments.*.file' => 'The selected file could not be uploaded – try again',
        ];
    }

    /**
     * @throws ImageRejected
     */
    public function store(Contact $author, UnionMail $mail, UploadedFile $file): UnionMailAttachment
    {
        $name = $this->safeName($file->getClientOriginalName());

        if (in_array($file->guessExtension(), self::IMAGE_TYPES, true)) {
            $image = $this->images->store($author, $file);

            return $mail->attachments()->create([
                'uploaded_image_id' => $image->id,
                'disk' => $image->disk,
                'path' => $image->path,
                // The image may have been converted, so the name gets its new extension.
                'name' => pathinfo($name, PATHINFO_FILENAME).'.'.pathinfo($image->path, PATHINFO_EXTENSION),
                'mime_type' => $image->mime_type,
                'size' => $image->size,
            ]);
        }

        $disk = config('uploads.attachments.disk');
        $path = $file->storeAs("mail-attachments/{$mail->building_id}", bin2hex(random_bytes(20)).'.'.$file->guessExtension(), $disk);

        return $mail->attachments()->create([
            'disk' => $disk,
            'path' => $path,
            'name' => $name,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Delete the stored file, for attachments of mail that was never created.
     */
    public function discard(UnionMailAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
    }

    private function safeName(string $name): string
    {
        $name = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\"]+/u', '', basename($name)) ?? '');

        return mb_substr($name !== '' ? $name : 'attachment', -200);
    }
}
