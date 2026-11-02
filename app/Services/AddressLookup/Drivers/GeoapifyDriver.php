<?php

namespace App\Services\AddressLookup\Drivers;

use App\Services\AddressLookup\AddressLookup;
use App\Services\AddressLookup\AddressResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Geoapify Autocomplete + Place Details, restricted to the UK.
 *
 * @see https://apidocs.geoapify.com/docs/geocoding/address-autocomplete/
 * @see https://apidocs.geoapify.com/docs/place-details/
 */
class GeoapifyDriver implements AddressLookup
{
    public const NAME = 'geoapify';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.geoapify.com',
    ) {}

    public function search(string $query): Collection
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(5)
            ->get('/v1/geocode/autocomplete', [
                'text' => $query,
                'filter' => 'countrycode:gb',
                'format' => 'json',
                'limit' => 8,
                'apiKey' => $this->apiKey,
            ])
            ->throw();

        return collect($response->json('results', []))
            ->filter(fn (array $result) => filled($result['place_id'] ?? null))
            ->map(fn (array $result) => $this->toResult($result))
            ->values();
    }

    public function find(string $providerId): ?AddressResult
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(5)
            ->get('/v2/place-details', [
                'id' => $providerId,
                'features' => 'details',
                'apiKey' => $this->apiKey,
            ]);

        if ($response->clientError()) {
            return null;
        }

        $properties = $response->throw()->json('features.0.properties');

        if (! is_array($properties)) {
            return null;
        }

        // Place details can return a different (more canonical) place_id; keep the one
        // that was searched for so matching against stored buildings is stable.
        return $this->toResult([...$properties, 'place_id' => $providerId]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function toResult(array $result): AddressResult
    {
        $line1 = $result['address_line1']
            ?? trim(($result['housenumber'] ?? '').' '.($result['street'] ?? ''))
            ?: ($result['formatted'] ?? '');

        // Geoapify's address_line2 is "city postcode, country" rather than a second
        // street line, so city and postcode are kept in their own fields instead.
        return new AddressResult(
            provider: self::NAME,
            providerId: (string) $result['place_id'],
            line1: $line1,
            line2: null,
            city: $result['city'] ?? null,
            postcode: $result['postcode'] ?? null,
            latitude: isset($result['lat']) ? (float) $result['lat'] : null,
            longitude: isset($result['lon']) ? (float) $result['lon'] : null,
            formatted: $result['formatted'] ?? $line1,
        );
    }
}
