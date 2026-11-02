<?php

use App\Services\AddressLookup\AddressLookup;
use App\Services\AddressLookup\Drivers\FakeDriver;
use App\Services\AddressLookup\Drivers\GeoapifyDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('resolves the configured driver', function () {
    expect(app(AddressLookup::class))->toBeInstanceOf(FakeDriver::class);

    config(['address.driver' => 'geoapify', 'address.drivers.geoapify.key' => 'test-key']);
    app()->forgetInstance(AddressLookup::class);

    expect(app(AddressLookup::class))->toBeInstanceOf(GeoapifyDriver::class);
});

it('searches addresses as JSON', function () {
    $this->getJson('/address/search?q=example')
        ->assertOk()
        ->assertExactJson([['id' => 'fake-1', 'label' => 'Example Court, 1 Example Street, London EC1A 1AA']]);

    $this->getJson('/address/search?q=ex')->assertUnprocessable();
});

it('maps Geoapify autocomplete results', function () {
    Http::fake([
        'api.geoapify.com/v1/geocode/autocomplete*' => Http::response(['results' => [[
            'place_id' => 'abc123',
            'address_line1' => '10 Downing Street',
            'address_line2' => 'London SW1A 2AA, United Kingdom',
            'city' => 'London',
            'postcode' => 'SW1A 2AA',
            'lat' => 51.5034,
            'lon' => -0.1276,
            'formatted' => '10 Downing Street, London SW1A 2AA, United Kingdom',
        ]]]),
    ]);

    $results = (new GeoapifyDriver('test-key'))->search('10 downing');

    expect($results)->toHaveCount(1)
        ->and($results[0]->provider)->toBe('geoapify')
        ->and($results[0]->providerId)->toBe('abc123')
        ->and($results[0]->line1)->toBe('10 Downing Street')
        ->and($results[0]->postcode)->toBe('SW1A 2AA');

    Http::assertSent(fn (Request $request) => $request['filter'] === 'countrycode:gb' && $request['apiKey'] === 'test-key');
});

it('finds a Geoapify place by ID, keeping the searched ID', function () {
    Http::fake([
        'api.geoapify.com/v2/place-details*' => Http::response(['features' => [['properties' => [
            'place_id' => 'canonical-id',
            'housenumber' => '10',
            'street' => 'Downing Street',
            'postcode' => 'SW1A 2AA',
            'formatted' => '10 Downing Street, London SW1A 2AA',
        ]]]]),
    ]);

    $result = (new GeoapifyDriver('test-key'))->find('abc123');

    expect($result->providerId)->toBe('abc123')
        ->and($result->line1)->toBe('10 Downing Street');
});

it('returns null for an unknown Geoapify place', function () {
    Http::fake(['api.geoapify.com/*' => Http::response(['error' => 'Bad Request'], 400)]);

    expect((new GeoapifyDriver('test-key'))->find('nope'))->toBeNull();
});
