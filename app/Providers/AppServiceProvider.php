<?php

namespace App\Providers;

use App\Models\Bulletin;
use App\Models\Constitution;
use App\Models\Contact;
use App\Models\Policy;
use App\Models\Thread;
use App\Models\User;
use App\Services\AddressLookup\AddressLookup;
use App\Services\AddressLookup\Drivers\FakeDriver;
use App\Services\AddressLookup\Drivers\GeoapifyDriver;
use App\Services\InboundMail\Drivers\FakeDriver as FakeInboundMailbox;
use App\Services\InboundMail\Drivers\ImapDriver;
use App\Services\InboundMail\InboundMailbox;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AddressLookup::class, fn () => match (config('address.driver')) {
            GeoapifyDriver::NAME => new GeoapifyDriver((string) config('address.drivers.geoapify.key')),
            FakeDriver::NAME => new FakeDriver,
            default => throw new InvalidArgumentException('Unknown address lookup driver ['.config('address.driver').'].'),
        });

        $this->app->singleton(InboundMailbox::class, fn () => match (config('inbound.driver')) {
            ImapDriver::NAME => new ImapDriver(config('inbound.drivers.imap')),
            FakeInboundMailbox::NAME => new FakeInboundMailbox,
            default => throw new InvalidArgumentException('Unknown inbound mail driver ['.config('inbound.driver').'].'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'bulletin' => Bulletin::class,
            'constitution' => Constitution::class,
            'policy' => Policy::class,
            'thread' => Thread::class,
        ]);

        // Members may manage their own union's landlords, external contacts and
        // mailing lists. Tenant contacts are never managed through these tools.
        // Refusals are 404s so other unions can't confirm a record exists.
        Gate::define('manage-union-record', function (User $user, Model $record): Response {
            $member = $user->contact;

            $allowed = $member?->isEnrolled()
                && $record->getAttribute('building_id') === $member->building_id
                && ! ($record instanceof Contact && $record->user()->exists());

            return $allowed ? Response::allow() : Response::denyAsNotFound();
        });

        // Members may read and reply to any of their union's bulletins. Tenants
        // waiting to be vouched for may only see the vouching bulletin.
        Gate::define('view-bulletin', function (User $user, Bulletin $bulletin): Response {
            $contact = $user->contact;

            $allowed = $contact
                && $bulletin->building_id === $contact->building_id
                && ($contact->isEnrolled() || ($bulletin->isVouching() && $contact->isConfirmedTenant()));

            return $allowed ? Response::allow() : Response::denyAsNotFound();
        });
    }
}
