<?php

use App\Jobs\WarmImageCache;
use App\Models\Building;
use App\Models\Contact;
use App\Models\UploadedImage;
use App\Support\RichText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('public');

    $this->building = Building::factory()->create();
    $this->member = Contact::factory()->tenantOf($this->building)->enrolled()->create();
});

/**
 * A PNG made with GD, transparent or not.
 */
function pngUpload(int $width, int $height, bool $transparent): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagesavealpha($image, true);
    imagealphablending($image, false);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 200, 50, 50, $transparent ? 127 : 0));

    $path = tempnam(sys_get_temp_dir(), 'png');
    imagepng($image, $path);

    return new UploadedFile($path, 'drawing.png', 'image/png', null, true);
}

/**
 * A JPEG with EXIF orientation 6 (rotate 90° clockwise) and a comment, as phones write them.
 */
function phonePhoto(int $width, int $height): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();

    $tiff = "II*\x00".pack('V', 8).pack('v', 1).pack('vvVv', 0x0112, 3, 1, 6)."\x00\x00".pack('V', 0);
    $exif = "Exif\x00\x00".$tiff;
    $comment = 'GPS 51.5203,-0.0977 SECRET';
    $segments = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif."\xFF\xFE".pack('n', strlen($comment) + 2).$comment;

    $path = tempnam(sys_get_temp_dir(), 'jpg');
    file_put_contents($path, substr($jpeg, 0, 2).$segments.substr($jpeg, 2));

    return new UploadedFile($path, 'IMG_0001.jpg', 'image/jpeg', null, true);
}

function upload(Contact $member, UploadedFile $file): TestResponse
{
    return test()->actingAs($member->user)->postJson(route('uploads.images.store'), ['file' => $file]);
}

it('shrinks large photos to 2000px and stores them as JPEG', function () {
    $response = upload($this->member, UploadedFile::fake()->image('lift.jpg', 3000, 1500))->assertCreated();

    $image = UploadedImage::sole();
    expect($response->json('location'))->toBe(url(Storage::disk('public')->url($image->path)))
        ->and($image->path)->toStartWith("uploads/{$this->building->id}/")->toEndWith('.jpg')
        ->and([$image->width, $image->height])->toBe([2000, 1000])
        ->and($image->uploader_contact_id)->toBe($this->member->id)
        ->and(getimagesizefromstring(Storage::disk('public')->get($image->path))[2])->toBe(IMAGETYPE_JPEG);
});

it('applies the phone\'s rotation and strips metadata', function () {
    upload($this->member, phonePhoto(300, 100))->assertCreated();

    $image = UploadedImage::sole();
    $stored = Storage::disk('public')->get($image->path);

    expect([$image->width, $image->height])->toBe([100, 300])
        ->and($stored)->not->toContain('Exif')
        ->and($stored)->not->toContain('SECRET');
});

it('keeps transparent images as PNG and converts opaque ones to JPEG', function () {
    upload($this->member, pngUpload(40, 40, transparent: true))->assertCreated();
    upload($this->member, pngUpload(40, 40, transparent: false))->assertCreated();

    expect(UploadedImage::pluck('mime_type')->all())->toBe(['image/png', 'image/jpeg']);
});

it('rejects files that are not images', function (UploadedFile $file) {
    upload($this->member, $file)->assertUnprocessable();

    expect(UploadedImage::count())->toBe(0);
})->with([
    'svg' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
    'text renamed' => fn () => UploadedFile::fake()->createWithContent('photo.png', 'not an image'),
    'too big' => fn () => UploadedFile::fake()->image('huge.jpg')->size(11_000),
]);

it('only lets members upload', function () {
    $tenant = Contact::factory()->tenantOf($this->building)->create();

    upload($tenant, UploadedFile::fake()->image('lift.jpg'))->assertRedirect(route('dashboard'));

    expect(UploadedImage::count())->toBe(0);
});

it('warms Cloudflare\'s cache after upload when enabled', function () {
    Queue::fake();
    config(['uploads.warm_cache' => true]);

    upload($this->member, UploadedFile::fake()->image('lift.jpg'))->assertCreated();

    Queue::assertPushed(WarmImageCache::class);

    Http::fake(['*' => Http::response('', 200)]);
    (new WarmImageCache(UploadedImage::sole()))->handle();

    expect(UploadedImage::sole()->cache_warmed_at)->not->toBeNull();
    Http::assertSent(fn ($request) => $request->url() === UploadedImage::sole()->url());
});

it('keeps only uploaded images in rich text', function () {
    $ours = RichText::uploadsUrl().'1/abc.jpg';

    $html = RichText::clean(
        '<p><img src="'.$ours.'" alt="Broken lift" width="900" onerror="x()"></p>'
        .'<p><img src="https://tracker.example/pixel.gif"></p>'
        .'<p><img src="data:image/png;base64,iVBORw0KGgo="></p>'
        .'<p><img src="'.RichText::uploadsUrl().'../../secret.jpg"></p>'
        .'<p><img src="'.rtrim(RichText::uploadsUrl(), '/').'-other/x.jpg"></p>'
    );

    expect($html)->toBe('<p><img src="'.$ours.'" alt="Broken lift" /></p><p></p><p></p><p></p><p></p>')
        ->and(RichText::toPlainText($html))->toBe('[Image: Broken lift]')
        ->and(RichText::isBlank($html))->toBeFalse();
});
