<?php

namespace App\Services\AddressLookup;

use Illuminate\Support\Collection;

/**
 * A swappable UK address lookup provider. See docs/architecture/address-lookup.md.
 */
interface AddressLookup
{
    /**
     * Autocomplete suggestions for a partial address.
     *
     * @return Collection<int, AddressResult>
     */
    public function search(string $query): Collection;

    /**
     * Fetch one address by the provider's ID, or null if it doesn't exist.
     */
    public function find(string $providerId): ?AddressResult;
}
