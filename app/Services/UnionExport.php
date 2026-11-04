<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\UnionMailAttachment;
use App\Models\UploadedImage;
use App\Models\User;
use Carbon\CarbonInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * A zip of everything a union's members could see, made just before the union
 * is deleted and emailed to its members: a PDF per section, split into parts
 * of at most MAX_PAGES pages. See docs/architecture/admin.md.
 */
class UnionExport
{
    /** How long the download link in the email works. */
    public const KEEP_DAYS = 30;

    /** Exports up to this size are attached to the email as well as linked. */
    public const ATTACH_MAX_BYTES = 10 * 1024 * 1024;

    public const DISK = 'local';

    public const DIRECTORY = 'union-exports';

    /** By default, each section is split into PDFs of at most this many printed pages. */
    public const MAX_PAGES = 1000;

    /** Entries measured together when splitting a section. */
    public const BATCH_SIZE = 25;

    /**
     * Uploaded image URLs mapped to data URIs, for the union being exported.
     *
     * @var array<string, string>
     */
    private array $images = [];

    public function __construct(private int $maxPages = self::MAX_PAGES) {}

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

        $this->images = UploadedImage::where('building_id', $building->id)->get()
            ->mapWithKeys(fn (UploadedImage $image) => [$image->url() => $this->dataUri($image)])
            ->filter()
            ->all();

        $attachments = UnionMailAttachment::whereIn('union_mail_id', $building->unionMails()->select('id'))->get();

        foreach ($attachments as $attachment) {
            $name = "files/mail/{$attachment->union_mail_id}/{$attachment->id}-".Str::of($attachment->name)->replaceMatches('/[^A-Za-z0-9._ -]/', '_');
            $this->addFile($zip, $attachment->disk, $attachment->path, $name);
        }

        $exportedAt = now();
        $contents = [];

        foreach ($this->sections($building) as $number => [$slug, $title, $entries]) {
            $parts = $this->paginate($building, $slug, $title, $entries, $exportedAt);

            foreach ($parts as $index => $body) {
                $file = sprintf('%d-%s%s.pdf', $number + 1, $slug, count($parts) > 1 ? '-part-'.($index + 1) : '');
                $zip->addFromString($file, $this->pdf($building, $title, $body, $index + 1, count($parts), $exportedAt)->output());
                $contents[] = $file;
            }
        }

        $zip->addFromString('README.txt', view('exports.readme', [
            'building' => $building,
            'contents' => $contents,
            'hasAttachments' => $attachments->isNotEmpty(),
            'exportedAt' => $exportedAt,
        ])->render());
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

    /**
     * Each section of the export: [file name, title, entries].
     *
     * @return list<array{string, string, Collection<int, Model>}>
     */
    private function sections(Building $building): array
    {
        return [
            ['constitution', 'Constitution', collect([$building->currentConstitution])->filter()],
            ['policies', 'Policies', $building->policies()->orderBy('created_at')->get()],
            ['roles', 'Roles', $building->roles()->with('contacts')->orderBy('created_at')->get()],
            ['referendums', 'Referendums', $building->referendums()->orderBy('created_at')->get()],
            ['bulletins', 'Bulletins', $building->bulletins()->with(['author', 'replies.author', 'polls.answers'])->orderBy('published_at')->get()],
            ['discussions', 'Discussions', $building->threads()->with(['author', 'replies.author'])->orderBy('published_at')->get()],
            ['mail', 'Mail', $building->unionMails()->with(['author', 'mailingList', 'attachments'])->orderBy('created_at')->get()],
            ['mailing-lists', 'Mailing lists', $building->mailingLists()->with('contacts')->orderBy('name')->get()],
        ];
    }

    /**
     * Split a section into parts of at most maxPages printed pages.
     *
     * Entries are rendered in batches, each starting on a new page, so a part's
     * page count is at most the sum of its batches' counts measured alone. A
     * batch longer than maxPages is halved until it fits; only a single entry
     * longer than maxPages can make a part longer.
     *
     * @param  Collection<int, Model>  $entries
     * @return list<string> Each part's HTML body
     */
    private function paginate(Building $building, string $slug, string $title, Collection $entries, CarbonInterface $exportedAt): array
    {
        if ($entries->count() <= 1) {
            return [$this->renderEntries($slug, $entries, $exportedAt)];
        }

        $measure = fn (string $html) => $this->pdf($building, $title, $html, 1, 1, $exportedAt)->getCanvas()->get_page_count();
        $batches = [];
        $queue = $entries->chunk(self::BATCH_SIZE)->values()->all();

        while ($queue) {
            $batch = array_shift($queue);
            $html = $this->renderEntries($slug, $batch, $exportedAt);
            $pages = $measure($html);

            if ($pages > $this->maxPages && $batch->count() > 1) {
                [$first, $second] = $batch->values()->split(2)->all();
                array_unshift($queue, $first, $second);

                continue;
            }

            $batches[] = [$html, $pages];
        }

        $parts = [];
        $current = [];
        $total = 0;

        foreach ($batches as [$html, $pages]) {
            if ($current && $total + $pages > $this->maxPages) {
                $parts[] = $current;
                $current = [];
                $total = 0;
            }

            $current[] = $html;
            $total += $pages;
        }

        $parts[] = $current;

        return array_map(fn (array $part) => implode('', array_map(
            fn (string $batch, int $index) => $index === 0 ? $batch : "<div class=\"batch\">{$batch}</div>",
            $part,
            array_keys($part),
        )), $parts);
    }

    /**
     * @param  Collection<int, Model>  $entries
     */
    private function renderEntries(string $slug, Collection $entries, CarbonInterface $exportedAt): string
    {
        $html = $entries->isEmpty()
            ? '<p>None.</p>'
            : view("exports.sections.{$slug}", [
                'entries' => $entries,
                'fmt' => fn (?CarbonInterface $date) => $date?->format('j M Y, H:i'),
            ])->render();

        // Images are embedded, since the files they link to are deleted with the union.
        return strtr($html, $this->images);
    }

    private function pdf(Building $building, string $title, string $body, int $part, int $parts, CarbonInterface $exportedAt): Dompdf
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setChroot([storage_path('framework')]);
        $options->setDefaultPaperSize('a4');

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('exports.pdf', [
            'union' => $building->name,
            'address' => $building->formattedAddress(),
            'title' => $title,
            'body' => $body,
            'part' => $part,
            'parts' => $parts,
            'exportedAt' => $exportedAt,
        ])->render());
        $pdf->render();

        return $pdf;
    }

    private function dataUri(UploadedImage $image): ?string
    {
        $storage = Storage::disk($image->disk);

        return $storage->exists($image->path)
            ? "data:{$image->mime_type};base64,".base64_encode($storage->get($image->path))
            : null;
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
