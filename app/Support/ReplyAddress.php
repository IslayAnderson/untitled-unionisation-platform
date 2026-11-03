<?php

namespace App\Support;

/**
 * Plus addresses that route email replies back to the union mail they answer.
 */
class ReplyAddress
{
    public static function enabled(): bool
    {
        return str_contains((string) config('inbound.address'), '@');
    }

    /**
     * The Reply-To address for a mail's token, or null if inbound mail isn't set up.
     */
    public static function for(string $token): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        [$local, $domain] = explode('@', config('inbound.address'), 2);

        return "{$local}+{$token}@{$domain}";
    }

    /**
     * The token from the first recipient that is one of our reply addresses.
     *
     * @param  array<int, string>  $recipients
     */
    public static function tokenFrom(array $recipients): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        [$local, $domain] = explode('@', config('inbound.address'), 2);
        $pattern = '/^'.preg_quote($local, '/').'\+([a-f0-9]{24})@'.preg_quote($domain, '/').'$/i';

        foreach ($recipients as $recipient) {
            if (preg_match($pattern, trim($recipient), $match)) {
                return strtolower($match[1]);
            }
        }

        return null;
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(12));
    }
}
