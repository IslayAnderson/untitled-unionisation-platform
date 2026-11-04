<?php

use App\Support\RichText;

/**
 * Posted HTML is shown to every member, so nothing in it may make their
 * browser load anything from another site: images, styles, frames, refreshes.
 */
it('drops anything in posted HTML that would load from another site', function (string $html) {
    $html = str_replace(['UPLOADS/', 'HOST'], [RichText::uploadsUrl(), parse_url(RichText::uploadsUrl(), PHP_URL_HOST)], $html);
    $clean = RichText::clean($html);

    expect($clean)->not->toContain('evil.test')
        ->and($clean)->not->toMatch('/<(style|noscript|link|iframe|object|embed|video|audio|source|svg|math|template|meta|base|form|input|table|body)\b/i')
        ->and($clean)->not->toMatch('/\s(style|srcset|ping|background|poster|data|action)=/i')
        ->and($clean)->not->toContain('data:');
})->with([
    'image from another site' => '<img src="https://evil.test/x.png">',
    'protocol-relative image' => '<img src="//evil.test/x.png">',
    'srcset on an uploaded image' => '<img src="UPLOADS/1/a.jpg" srcset="https://evil.test/x.png 2x">',
    'our host as a user name' => '<img src="https://HOST@evil.test/x.png">',
    'lookalike host' => '<img src="https://HOST.evil.test/uploads/x.png">',
    'uploads URL climbing out' => '<img src="UPLOADS/../../evil.test/x.png">',
    'data URI image' => '<img src="data:image/svg+xml,<svg onload=alert(1)>">',
    'noscript image' => '<noscript><img src="https://evil.test/ns.png"></noscript>',
    'style element' => '<style>body{background:url(https://evil.test/s.png)}</style><p>x</p>',
    'style import' => '<style>@import "https://evil.test/s.css";</style><p>x</p>',
    'style attribute' => '<p style="background:url(https://evil.test/a.png)">x</p>',
    'stylesheet link' => '<link rel="stylesheet" href="https://evil.test/s.css">',
    'iframe' => '<iframe src="https://evil.test/"></iframe>',
    'object and embed' => '<object data="https://evil.test/o"></object><embed src="https://evil.test/e">',
    'video and audio' => '<video poster="https://evil.test/p.png"><source src="https://evil.test/v.mp4"></video><audio src="https://evil.test/a.mp3"></audio>',
    'svg image' => '<svg><image href="https://evil.test/i.png"/></svg>',
    'table background' => '<table background="https://evil.test/t.png"><tr><td>x</td></tr></table>',
    'image input' => '<input type="image" src="https://evil.test/in.png">',
    'link ping' => '<a href="https://example.org" ping="https://evil.test/ping">x</a>',
    'meta refresh' => '<meta http-equiv="refresh" content="0;url=https://evil.test">',
    'base' => '<base href="https://evil.test/">',
    'form' => '<form action="https://evil.test"><button>x</button></form>',
    'template' => '<template><img src="https://evil.test/t.png"></template>',
    'body background' => '<body background="https://evil.test/b.png"><p>x</p></body>',
    'unquoted broken image' => '<p><img src=https://evil.test/u.png</p>',
]);

it('keeps images uploaded to the platform', function () {
    $src = RichText::uploadsUrl().'1/photo.jpg';

    expect(RichText::clean('<p><img src="'.$src.'" alt="The lift"></p>'))
        ->toBe('<p><img src="'.$src.'" alt="The lift" /></p>');
});
