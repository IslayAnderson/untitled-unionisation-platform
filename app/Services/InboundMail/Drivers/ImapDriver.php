<?php

namespace App\Services\InboundMail\Drivers;

use App\Services\InboundMail\InboundEmail;
use App\Services\InboundMail\InboundMailbox;
use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Attachment;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MessageInterface;

/**
 * Reads unseen messages from an IMAP folder and marks them seen once processed.
 */
class ImapDriver implements InboundMailbox
{
    public const NAME = 'imap';

    private ?FolderInterface $folder = null;

    /** @var array<string, MessageInterface> */
    private array $messages = [];

    /**
     * @param  array{host: ?string, port: int, encryption: ?string, username: ?string, password: ?string, folder: string}  $config
     */
    public function __construct(private array $config) {}

    public function unprocessed(int $limit = 50): iterable
    {
        $messages = $this->folder()->messages()
            ->unseen()
            ->withHeaders()
            ->withBody()
            ->oldest()
            ->limit($limit)
            ->get();

        foreach ($messages as $message) {
            $this->messages[(string) $message->uid()] = $message;

            yield $this->toEmail($message);
        }
    }

    public function markProcessed(InboundEmail $email): void
    {
        ($this->messages[$email->id] ?? null)?->markSeen();
        unset($this->messages[$email->id]);
    }

    private function folder(): FolderInterface
    {
        return $this->folder ??= (new Mailbox([
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'encryption' => $this->config['encryption'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
        ]))->folders()->findOrFail($this->config['folder']);
    }

    private function toEmail(MessageInterface $message): InboundEmail
    {
        $recipients = collect([...$message->to(), ...$message->cc(), ...$message->addresses('Delivered-To')])
            ->map(fn (Address $address) => $address->email())
            ->filter()
            ->unique()
            ->values()
            ->all();

        return new InboundEmail(
            id: (string) $message->uid(),
            messageId: $message->messageId(),
            fromEmail: (string) $message->from()?->email(),
            fromName: $message->from()?->name() ?: null,
            recipients: $recipients,
            subject: (string) $message->subject(),
            text: $message->text(),
            html: $message->html(),
            date: $message->date(),
            attachments: array_map(fn (Attachment $attachment) => [
                'filename' => $attachment->filename() ?? 'attachment',
                'content_type' => $attachment->contentType(),
                'contents' => $attachment->contents(),
            ], $message->attachments()),
        );
    }
}
