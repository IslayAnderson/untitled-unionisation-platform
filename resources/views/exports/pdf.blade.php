{{--
    One PDF of a union's export (App\Services\UnionExport): a section, or one part of a long section.
    $body is the section's rendered batches. dompdf supports only CSS 2.1 and a little more, so keep
    styles simple. Rich text is stored cleaned (App\Support\RichText).
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $union }}: {{ $title }}</title>
    <style>
        @page { margin: 22mm 18mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.45; color: #1c1917; }
        header { position: fixed; top: -14mm; left: 0; right: 0; font-size: 8pt; color: #78716c; border-bottom: 0.5pt solid #d6d3d1; padding-bottom: 2mm; }
        footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8pt; color: #78716c; text-align: right; }
        footer .page:after { content: counter(page); }
        h1 { font-size: 20pt; margin: 0 0 2mm; }
        h2 { font-size: 13pt; margin: 8mm 0 1mm; }
        .meta { color: #78716c; font-size: 8.5pt; margin: 0 0 2mm; }
        .box { border: 0.5pt solid #d6d3d1; padding: 2mm 3mm; margin-top: 3mm; }
        .reply { border-left: 1.5pt solid #d6d3d1; padding-left: 4mm; margin-top: 4mm; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 1mm 2mm 1mm 0; border-bottom: 0.5pt solid #e7e5e4; vertical-align: top; }
        img { max-width: 100%; }
        .batch { page-break-before: always; }
    </style>
</head>
<body>
    <header>{{ $union }} · {{ $title }}@if ($parts > 1) · part {{ $part }} of {{ $parts }}@endif · exported {{ $exportedAt->format('j M Y') }}</header>
    <footer>Page <span class="page"></span></footer>

    <h1>{{ $title }}@if ($parts > 1) <span class="meta">(part {{ $part }} of {{ $parts }})</span>@endif</h1>
    <p class="meta">{{ $union }} · {{ $address }}</p>

    {!! $body !!}
</body>
</html>
