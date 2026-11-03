<?php

namespace App\Services\InboundMail\Drivers;

use App\Services\InboundMail\InboundEmail;
use App\Services\InboundMail\InboundMailbox;

/**
 * An in-memory mailbox for tests and for local development without IMAP.
 */
class FakeDriver implements InboundMailbox
{
    public const NAME = 'fake';

    /** @var array<string, InboundEmail> */
    private array $emails = [];

    /** @var array<int, string> */
    public array $processed = [];

    public function receive(InboundEmail $email): void
    {
        $this->emails[$email->id] = $email;
    }

    public function unprocessed(int $limit = 50): iterable
    {
        return array_slice(array_values(array_filter(
            $this->emails,
            fn (InboundEmail $email) => ! in_array($email->id, $this->processed, true),
        )), 0, $limit);
    }

    public function markProcessed(InboundEmail $email): void
    {
        $this->processed[] = $email->id;
    }
}
