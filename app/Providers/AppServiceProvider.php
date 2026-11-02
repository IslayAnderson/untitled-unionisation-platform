<?php

namespace App\Providers;

use App\Models\Constitution;
use App\Models\Policy;
use App\Services\AddressLookup\AddressLookup;
use App\Services\AddressLookup\Drivers\FakeDriver;
use App\Services\AddressLookup\Drivers\GeoapifyDriver;
use Illuminate\Database\Eloquent\Relations\Relation;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'constitution' => Constitution::class,
            'policy' => Policy::class,
        ]);
    }
}
