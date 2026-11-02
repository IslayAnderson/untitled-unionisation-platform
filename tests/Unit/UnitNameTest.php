<?php

use App\Models\Unit;

it('normalises unit names for deduping', function (string $input, string $expected) {
    expect(Unit::normalise($input))->toBe($expected);
})->with([
    ['Flat 4', 'flat 4'],
    ['  FLAT   4 ', 'flat 4'],
    ['Flat 4.', 'flat 4'],
    ['Room 2B', 'room 2b'],
    ['Flat 4-A', 'flat 4 a'],
]);
