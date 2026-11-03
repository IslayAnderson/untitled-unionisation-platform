<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\UnionMailAttachment;
use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * A zip of everything a union's members could see, made just before the union
 * is deleted and emailed to its members. See docs/architecture/admin.md.
 */
class UnionExport
{
    /** How long the download link in the email works. */
    public const KEEP_DAYS = 30;

    /** Exports up to this size are attached to the email as well as linked. */
    public const ATTACH_MAX_BYTES = 10 * 1024 * 1024;

    public const DISK = 'local';

    public const DIRECTORY = 'union-exports';

    /**
     * Members' login addresses, which receive the export.
     *
     * @return list<string>
     */
    public function recipients(Building $building): array
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->whereIn('contact_id', $building->contacts()->whereNotNull('enrolled_at')->select('id'))
            ->pluck('email')
            ->all();
    }

    /**
     * Build the zip and store it. Returns its path on DISK.
     */
    public function create(Building $building): string
    {
        $directory = self::DIRECTORY.'/'.Str::random(40);
        $path = $directory.'/'.Str::slug($building->name).'-export.zip';
        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory($directory);

        $zip = new ZipArchive;

        if ($zip->open($disk->path($path), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Couldn't create the export for union {$building->id}.");
        }

        $images = UploadedImage::where('building_id', $building->id)->get();
        $imagePaths = [];

        foreach ($images as $image) {
            $name = 'files/images/'.basename($image->path);
            $this->addFile($zip, $image->disk, $image->path, $name);
            $imagePaths[$image->url()] = $name;
        }

        $attachments = UnionMailAttachment::whereIn('union_mail_id', $building->unionMails()->select('id'))->get();
        $attachmentPaths = [];

        foreach ($attachments as $attachment) {
            $name = "files/mail/{$attachment->union_mail_id}/{$attachment->id}-".Str::of($attachment->name)->replaceMatches('/[^A-Za-z0-9._ -]/', '_');
            $this->addFile($zip, $attachment->disk, $attachment->path, $name);
            $attachmentPaths[$attachment->id] = $name;
        }

        $html = view('exports.union', [
            'building' => $building->load([
                'currentConstitution',
                'policies' => fn ($query) => $query->orderBy('created_at'),
                'roles' => fn ($query) => $query->with('contacts')->orderBy('created_at'),
                'referendums' => fn ($query) => $query->with('candidate')->orderBy('created_at'),
                'bulletins' => fn ($query) => $query->with(['author', 'replies.author', 'polls.answers'])->orderBy('published_at'),
                'threads' => fn ($query) => $query->with(['author', 'replies.author'])->orderBy('published_at'),
                'unionMails' => fn ($query) => $query->with(['author', 'mailingList', 'attachments'])->orderBy('created_at'),
                'landlords',
                'mailingLists.contacts',
            ]),
            'attachmentPaths' => $attachmentPaths,
            'exportedAt' => now(),
        ])->render();

        $zip->addFromString('index.html', strtr($html, $imagePaths));
        $zip->addFromString('landlords.csv', $this->csv(
            ['Name', 'Address', 'Telephone', 'Email', 'Company number'],
            $building->landlords->map(fn (Landlord $landlord) => [$landlord->name, $landlord->address, $landlord->telephone, $landlord->email, $landlord->company_number]),
        ));
        $zip->addFromString('contacts.csv', $this->csv(
            ['Name', 'Landlord', 'Email', 'Telephone', 'Address', 'Mailing lists'],
            Contact::query()
                ->whereDoesntHave('user')
                ->where(fn ($query) => $query->where('building_id', $building->id)->orWhereIn('landlord_id', $building->landlords->modelKeys()))
                ->with(['landlord', 'mailingLists'])
                ->get()
                ->map(fn (Contact $contact) => [$contact->name, $contact->landlord?->name, $contact->email, $contact->telephone, $contact->address, $contact->mailingLists->pluck('name')->implode('; ')]),
        ));
        $zip->close();

        return $path;
    }

    /**
     * A link to download the export, valid for KEEP_DAYS.
     */
    public function downloadUrl(string $path): string
    {
        return URL::temporarySignedRoute('union-exports.download', now()->addDays(self::KEEP_DAYS), [
            'export' => basename(dirname($path)),
        ]);
    }

    /**
     * Delete exports older than KEEP_DAYS.
     */
    public function prune(): int
    {
        $disk = Storage::disk(self::DISK);
        $pruned = 0;

        foreach ($disk->directories(self::DIRECTORY) as $directory) {
            $files = $disk->files($directory);

            if (! $files || $disk->lastModified($files[0]) < now()->subDays(self::KEEP_DAYS)->getTimestamp()) {
                $disk->deleteDirectory($directory);
                $pruned++;
            }
        }

        return $pruned;
    }

    private function addFile(ZipArchive $zip, string $disk, string $path, string $name): void
    {
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            $zip->addFromString($name, $storage->get($path));
        }
    }

    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function csv(array $headings, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headings, escape: '');

        foreach ($rows as $row) {
            // Stop spreadsheets running cells as formulas.
            fputcsv($handle, array_map(fn ($value) => preg_match('/^[=+\-@\t\r]/', (string) $value) ? "'".$value : $value, $row), escape: '');
        }

        rewind($handle);

        return stream_get_contents($handle);
    }
}
