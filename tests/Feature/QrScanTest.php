<?php

use App\Enums\QrScanKind;
use App\Models\Building;
use App\Models\Contact;
use App\Models\QrScan;
use App\Models\User;
use App\Support\QrCode;

it('marks the URL a QR code opens as scanned', function () {
    expect(QrCode::scannedUrl('https://example.test/join/abc'))->toBe('https://example.test/join/abc?via=qr')
        ->and(QrCode::scannedUrl('https://example.test/?a=1'))->toBe('https://example.test/?a=1&via=qr');
});

it('counts a join code being scanned once per session, without recording who scanned it', function () {
    $building = Building::factory()->create();

    $this->get(QrCode::scannedUrl($building->joinUrl()))->assertOk();
    $this->get(QrCode::scannedUrl($building->joinUrl()))->assertOk();
    $this->get($building->joinUrl())->assertOk();

    $scan = QrScan::sole();
    expect($scan->kind)->toBe(QrScanKind::Join)
        ->and($scan->building_id)->toBe($building->id)
        ->and(array_keys($scan->getAttributes()))->toEqualCanonicalizing(['id', 'building_id', 'kind', 'created_at']);
});

it('counts vouching, campaign and demo codes', function () {
    $building = Building::factory()->create();
    [$member, $vouchee] = Contact::factory()->count(2)->tenantOf($building)->create()->all();

    $this->actingAs($member->user)->get(QrCode::scannedUrl(route('vouch.show', $vouchee->vouch_token)))->assertOk();
    $this->get(QrCode::scannedUrl(route('home')))->assertOk();
    $this->get(QrCode::scannedUrl(route('demo.show')));

    expect(QrScan::where('kind', QrScanKind::Vouch)->sole()->building_id)->toBe($building->id)
        ->and(QrScan::where('kind', QrScanKind::Campaign)->sole()->building_id)->toBeNull()
        ->and(QrScan::where('kind', QrScanKind::Demo)->count())->toBe(1);
});

it('shows admins how many times QR codes were scanned', function () {
    $admin = User::factory()->create()->forceFill(['is_admin' => true]);
    $admin->save();
    $building = Building::factory()->create();
    QrScan::factory()->count(3)->for($building)->create();
    QrScan::factory()->for($building)->create(['kind' => QrScanKind::Vouch]);
    QrScan::factory()->create(['building_id' => null, 'kind' => QrScanKind::Campaign]);

    $this->actingAs($admin)->get(route('admin.unions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Join codes scanned', '3', 'Vouching codes scanned', '1', 'Campaign leaflets scanned', '1', 'All QR code scans', '5']);

    $this->actingAs($admin)->get(route('admin.unions.show', $building))
        ->assertOk()
        ->assertSeeInOrder(['QR code scans', 'Join code', '3', 'Members\' vouching codes', '1', 'All', '4'], escape: false);
});
