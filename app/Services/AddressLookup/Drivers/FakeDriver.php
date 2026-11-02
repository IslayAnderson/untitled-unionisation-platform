<?php

namespace App\Services\AddressLookup\Drivers;

use App\Services\AddressLookup\AddressLookup;
use App\Services\AddressLookup\AddressResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * In-memory addresses for tests and for local development without an API key.
 */
class FakeDriver implements AddressLookup
{
    public const NAME = 'fake';

    /** @var Collection<int, AddressResult> */
    private Collection $addresses;

    /**
     * @param  array<int, array<string, mixed>>|null  $addresses
     */
    public function __construct(?array $addresses = null)
    {
        $this->addresses = collect($addresses ?? self::defaults())
            ->map(fn (array $address) => AddressResult::fromArray(['provider' => self::NAME, ...$address]));
    }

    public function search(string $query): Collection
    {
        $needle = Str::lower(Str::squish($query));

        return $this->addresses
            ->filter(fn (AddressResult $address) => Str::contains(Str::lower($address->formatted), $needle))
            ->values();
    }

    public function find(string $providerId): ?AddressResult
    {
        return $this->addresses->first(fn (AddressResult $address) => $address->providerId === $providerId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'provider_id' => 'fake-1',
                'line1' => '1 Example Street',
                'city' => 'London',
                'postcode' => 'EC1A 1AA',
                'latitude' => 51.5203,
                'longitude' => -0.0977,
                'formatted' => 'Example Court, 1 Example Street, London EC1A 1AA',
            ],
            [
                'provider_id' => 'fake-2',
                'line1' => '22 Sample Road',
                'city' => 'Manchester',
                'postcode' => 'M1 1AE',
                'latitude' => 53.4808,
                'longitude' => -2.2426,
                'formatted' => '22 Sample Road, Manchester M1 1AE',
            ],
            [
                'provider_id' => 'fake-3',
                'line1' => '5 Placeholder Lane',
                'city' => 'Glasgow',
                'postcode' => 'G1 1AA',
                'latitude' => 55.8609,
                'longitude' => -4.2514,
                'formatted' => 'Placeholder House, 5 Placeholder Lane, Glasgow G1 1AA',
            ],
        ];
    }
}
