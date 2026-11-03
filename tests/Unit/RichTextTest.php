<?php

use App\Support\EmailReplyText;
use App\Support\RichText;

it('turns rich text into readable plain text', function () {
    $html = '<h2>Repairs</h2><p>Fix the <strong>lift</strong> &amp; heating.<br>Now.</p><ul><li>Lift</li><li>Boiler</li></ul><p><a href="https://example.org">Our demands</a></p>';

    expect(RichText::toPlainText($html))->toBe("Repairs\n\nFix the lift & heating.\nNow.\n\n- Lift\n\n- Boiler\n\nOur demands (https://example.org)");
});

it('turns plain text into escaped paragraphs', function () {
    expect(RichText::fromPlainText("Hello <b>you</b>\nthere\n\n\nBye"))->toBe('<p>Hello &lt;b&gt;you&lt;/b&gt;<br>there</p><p>Bye</p>');
});

it('knows when rich text is blank', function (string $html, bool $blank) {
    expect(RichText::isBlank($html))->toBe($blank);
})->with([
    'empty paragraph' => ['<p>&nbsp;</p>', true],
    'line breaks' => ['<p><br></p><p> </p>', true],
    'text' => ['<p>Hi</p>', false],
]);

it('strips quoted history from email replies', function (string $text, string $expected) {
    expect(EmailReplyText::strip($text))->toBe($expected);
})->with([
    'gmail' => ["Thanks, noted.\n\nOn Tue, 3 Nov 2026 at 09:00, Example Court <a@b.c> wrote:\n> Original", 'Thanks, noted.'],
    'gmail wrapped' => ["Thanks.\n\nOn Tue, 3 Nov 2026 at 09:00, Example Court\n<a@b.c> wrote:\n> Original", 'Thanks.'],
    'outlook' => ["Received.\n\n-----Original Message-----\nFrom: Example Court", 'Received.'],
    'outlook headers' => ["Received.\n\nFrom: Example Court <a@b.c>\nSent: Tuesday", 'Received.'],
    'quoted lines' => ["Yes.\n> Will you fix it?", 'Yes.'],
    'no quote' => ["Line one\nLine two", "Line one\nLine two"],
]);
