# Address lookup

Address search goes through a small harness so the provider can be swapped without touching the
signup code.

* `App\Services\AddressLookup\AddressLookup`: the interface
  * `search(string $query): Collection<AddressResult>`: autocomplete suggestions
  * `find(string $providerId): ?AddressResult`: re-fetch one address by ID (used server-side so a
    submitted address can't be forged)
* `App\Services\AddressLookup\AddressResult`: the value object (`provider`, `providerId`,
  `line1`, `line2`, `city`, `postcode`, `latitude`, `longitude`, `formatted`)
* Drivers live in `App\Services\AddressLookup\Drivers`:
  * `GeoapifyDriver` (default): Geoapify Autocomplete + Place Details APIs, filtered to the UK.
  * `FakeDriver`: an in-memory driver for tests and local development without an API key.

The driver is chosen in `config/address.php` with `ADDRESS_LOOKUP_DRIVER` (`geoapify` | `fake`).
The Geoapify key goes in `GEOAPIFY_API_KEY`.

## Matching buildings
Buildings are matched on (`address_provider`, `address_provider_id`). Geoapify's `place_id` is
stable for a given address but has no UPRN. If the provider changes, existing buildings keep their
old provider ID, so a migration to re-key them (or a fallback match on normalised postcode + line 1)
will be needed at that point.

Results are building-level: flat/unit numbers are captured separately as the Unit name.
