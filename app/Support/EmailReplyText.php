<?php

namespace App\Support;

/**
 * The new part of an email reply, without the quoted message it replies to.
 */
class EmailReplyText
{
    /**
     * Lines that start the quoted history in common mail clients.
     *
     * @var array<int, string>
     */
    private const QUOTE_HEADERS = [
        '/^On .+wrote:\s*$/i',
        '/^-{2,}\s*Original Message\s*-{2,}\s*$/i',
        '/^_{10,}\s*$/',
        '/^From:\s.+$/i',
        '/^Sent from my /i',
    ];

    public static function strip(string $text): string
    {
        $lines = preg_split('/\R/', str_replace("\r\n", "\n", $text));
        $kept = [];

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);

            // Gmail wraps a long "On ... wrote:" onto two lines.
            $joined = $trimmed.' '.trim($lines[$index + 1] ?? '');

            if (str_starts_with($trimmed, '>')
                || collect(self::QUOTE_HEADERS)->contains(fn (string $pattern) => preg_match($pattern, $trimmed))
                || (str_starts_with($trimmed, 'On ') && preg_match(self::QUOTE_HEADERS[0], $joined))) {
                break;
            }

            $kept[] = rtrim($line);
        }

        return trim(implode("\n", $kept));
    }
}
