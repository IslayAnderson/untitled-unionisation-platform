# Image uploads

Members can add images to bulletins, instant mail and replies in the rich text editor: picked from
the image button, pasted, or dropped in. Each image is uploaded as soon as it's added
(`POST /uploads/images`, members only, 30 a minute, up to 10 MB).

## Compression
`App\Services\ImageUploads` re-encodes every image with GD:
* JPEG, PNG, GIF and WebP are accepted; anything else (including SVG) is refused, as are images over
  40 megapixels.
* The EXIF orientation is applied, then the image is shrunk to at most **2000px** on its longest side.
* It's saved as **JPEG** (quality 82, progressive), or as **PNG** if it has transparent pixels, so it
  shows in every email client. Animated GIFs become still images.
* Re-encoding drops **all metadata**, including camera GPS location, which could show where a tenant
  lives.

Files are stored at `uploads/{building_id}/{random}.jpg|png` on the `UPLOADS_DISK`. Each upload is
recorded in `uploaded_images` with the uploader, so the union and platform know who uploaded what.
Images are never deleted automatically.

## Only our own images
`RichText::clean()` keeps an `<img>` only if its `src` is under the uploads URL (no `..`), with just
`src` and `alt`. External images, tracking pixels and `data:` URLs are removed, so every image shown
or emailed has been compressed and served through Cloudflare (below).

Image URLs are random and public, since email clients (including landlords') must be able to load
them. Members are reminded to leave people, door numbers, post and anything identifying out of
photos.

## CSAM scanning
Cloudflare's [CSAM Scanning Tool](https://developers.cloudflare.com/cache/reference/csam-scanning/)
has no API to submit images to. Once enabled for a website, it hashes every image **as it enters
Cloudflare's cache** and compares it with known abuse images. On a match, Cloudflare blocks that URL
and emails the address given when enabling it. Since February 2025 it needs only a verified email,
not NCMEC credentials.

So in production every image must be served through a Cloudflare-cached hostname:
1. Create an R2 bucket and connect a custom domain on a Cloudflare zone (e.g. `media.example.org`).
   Don't enable the `r2.dev` URL; it isn't cached, so images there wouldn't be scanned.
2. Enable the CSAM Scanning Tool for that zone in the Cloudflare dashboard (see the docs linked above).
3. Set `UPLOADS_DISK=r2`, the `R2_*` settings, `R2_URL=https://media.example.org`, and
   `UPLOADS_WARM_CACHE=true`.

With `UPLOADS_WARM_CACHE`, a queued `WarmImageCache` job fetches each new image through `R2_URL` once,
so it's cached and scanned straight away rather than when a member first opens it. It records
`cache_warmed_at`, and logs a warning if the image can't be fetched (for example because Cloudflare
has blocked it). `.jpg` and `.png` are cached by Cloudflare by default.

If Cloudflare reports a match, use `uploaded_images` to find the uploader and the union, and follow
Cloudflare's notice. Don't delete the record or file without checking your legal duty to preserve
it.

Locally, images go on the `public` disk (`php artisan storage:link`) and aren't scanned.
