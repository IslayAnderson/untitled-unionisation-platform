<?php

namespace App\Console\Commands;

use App\Services\InboundReplies;
use App\Support\ReplyAddress;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('union-mail:fetch-replies')]
#[Description('Add email replies to union mail to their bulletins')]
class FetchUnionMailReplies extends Command
{
    public function handle(): int
    {
        if (! ReplyAddress::enabled()) {
            $this->warn('Inbound mail is not set up. Set INBOUND_MAIL_ADDRESS to fetch replies.');

            return self::SUCCESS;
        }

        $added = app(InboundReplies::class)->fetch();

        $this->info("Added {$added} ".str('reply')->plural($added).'.');

        return self::SUCCESS;
    }
}
