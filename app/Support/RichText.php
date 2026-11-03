<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * HTML written in the rich text editor. Everything is cleaned before it is
 * stored, so stored rich text is safe to output unescaped.
 */
class RichText
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED = [
        'p' => [],
        'br' => [],
        'h2' => [],
        'h3' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
        'hr' => [],
        'a' => ['href'],
    ];

    /**
     * Strip everything the editor can't produce: scripts, styles, attributes and unsafe links.
     */
    public static function clean(string $html): string
    {
        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(200_000);

        foreach (self::ALLOWED as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        return trim((new HtmlSanitizer($config))->sanitize($html));
    }

    /**
     * Escaped HTML paragraphs from plain text, such as the body of an email.
     */
    public static function fromPlainText(string $text): string
    {
        return collect(preg_split('/\R{2,}/', trim($text)))
            ->filter(fn (string $paragraph) => trim($paragraph) !== '')
            ->map(fn (string $paragraph) => '<p>'.preg_replace('/\R/', '<br>', e(trim($paragraph))).'</p>')
            ->implode('');
    }

    /**
     * Readable plain text, for the text part of emails and for excerpts.
     */
    public static function toPlainText(string $html): string
    {
        $text = preg_replace_callback(
            '/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is',
            fn (array $match) => strip_tags($match[2]) === html_entity_decode($match[1]) ? $match[2] : "{$match[2]} ({$match[1]})",
            $html,
        );
        $text = preg_replace('/<li\b[^>]*>/i', '- ', $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/(p|h2|h3|li|ul|ol|blockquote)>|<hr\s*\/?>/i', "\n\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Whether the HTML has no visible text, such as an empty editor's "<p>&nbsp;</p>".
     */
    public static function isBlank(string $html): bool
    {
        return trim(self::toPlainText($html), " \t\n\r\0\x0B\u{A0}") === '';
    }
}
