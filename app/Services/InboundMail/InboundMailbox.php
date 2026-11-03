<?php

namespace App\Services\InboundMail;

/**
 * A swappable source of email replies to union mail. See docs/architecture/mail.md.
 */
interface InboundMailbox
{
    /**
     * Emails that haven't been processed yet, oldest first.
     *
     * @return iterable<int, InboundEmail>
     */
    public function unprocessed(int $limit = 50): iterable;

    /**
     * Mark an email so it isn't returned again.
     */
    public function markProcessed(InboundEmail $email): void;
}
