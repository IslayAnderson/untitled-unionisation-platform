<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Support\SampleEmails;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one of every email the platform can send, with made-up details, to
 * check how they look in a real inbox. Nothing is saved: the union, mail and
 * referendum in them don't exist, so their links lead nowhere.
 */
#[Signature('mail:samples {email : Where to send them}')]
#[Description('Send a sample of every email the platform sends to one address')]
class SendSampleEmails extends Command
{
    public function handle(): int
    {
        $to = $this->argument('email');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("{$to} isn't an email address.");

            return self::FAILURE;
        }

        $building = (new Building)->forceFill([
            'id' => 0,
            'name' => 'Sample Court Tenants\' Union',
            'address_line_1' => '1 Sample Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ]);

        foreach (SampleEmails::for($building, $to) as $sample) {
            $sample->message instanceof Mailable
                ? Mail::to($to)->sendNow($sample->message)
                : $sample->notifiable->notifyNow($sample->message);

            $this->line("Sent: {$sample->label}");
        }

        $this->info("Sent every sample email to {$to}.");

        return self::SUCCESS;
    }
}
