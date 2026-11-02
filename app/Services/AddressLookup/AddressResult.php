<?php

namespace App\Services\AddressLookup;

final readonly class AddressResult
{
    public function __construct(
        public string $provider,
        public string $providerId,
        public string $line1,
        public ?string $line2,
        public ?string $city,
        public ?string $postcode,
        public ?float $latitude,
        public ?float $longitude,
        public string $formatted,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            providerId: $data['provider_id'],
            line1: $data['line1'],
            line2: $data['line2'] ?? null,
            city: $data['city'] ?? null,
            postcode: $data['postcode'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            formatted: $data['formatted'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_id' => $this->providerId,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'formatted' => $this->formatted,
        ];
    }
}
