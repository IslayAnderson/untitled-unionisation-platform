<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reply Address
    |--------------------------------------------------------------------------
    |
    | Union mail is sent with a Reply-To of this address plus a token for the
    | mail, e.g. replies+3f9a...@example.org. The mailbox must accept plus
    | addresses. Leave it blank to send replies to the union's own inbox.
    |
    */

    'address' => env('INBOUND_MAIL_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Inbound Mailbox Driver
    |--------------------------------------------------------------------------
    |
    | Where replies are read from. See docs/architecture/mail.md.
    | Supported: "imap", "fake"
    |
    */

    'driver' => env('INBOUND_MAIL_DRIVER', 'imap'),

    'drivers' => [
        'imap' => [
            'host' => env('INBOUND_IMAP_HOST'),
            'port' => (int) env('INBOUND_IMAP_PORT', 993),
            'encryption' => env('INBOUND_IMAP_ENCRYPTION', 'ssl'),
            'username' => env('INBOUND_IMAP_USERNAME'),
            'password' => env('INBOUND_IMAP_PASSWORD'),
            'folder' => env('INBOUND_IMAP_FOLDER', 'INBOX'),
        ],
    ],

];
