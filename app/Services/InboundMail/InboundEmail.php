<?php

namespace App\Services\InboundMail;

use Carbon\CarbonInterface;

/**
 * An email received by the inbound mailbox, independent of the driver.
 */
readonly class InboundEmail
{
    /**
     * @param  array<int, string>  $recipients  To, Cc and Delivered-To addresses.
     * @param  array<int, array{filename: string, content_type: string, contents: string}>  $attachments
     */
    public function __construct(
        public string $id,
        public ?string $messageId,
        public string $fromEmail,
        public ?string $fromName,
        public array $recipients,
        public string $subject,
        public ?string $text,
        public ?string $html,
        public ?CarbonInterface $date = null,
        public array $attachments = [],
    ) {}
}
