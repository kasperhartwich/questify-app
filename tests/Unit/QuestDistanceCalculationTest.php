<?php

use App\Models\Quest;

it('calculates haversine distance between two known points', function () {
    // Copenhagen City Hall to Nørreport Station ≈ 1.17 km
    $distance = Quest::haversineDistance(55.6761, 12.5683, 55.6837, 12.5716);

    expect($distance)->toBeGreaterThan(0.8)
        ->and($distance)->toBeLessThan(1.5);
});

it('returns zero distance for the same point', function () {
    $distance = Quest::haversineDistance(55.6761, 12.5683, 55.6761, 12.5683);

    expect($distance)->toBe(0.0);
});

it('calculates long distance correctly', function () {
    // Copenhagen to London ≈ 955 km
    $distance = Quest::haversineDistance(55.6761, 12.5683, 51.5074, -0.1278);

    expect($distance)->toBeGreaterThan(900)
        ->and($distance)->toBeLessThan(1010);
});

it('is symmetric regardless of direction', function () {
    $forward = Quest::haversineDistance(55.6761, 12.5683, 51.5074, -0.1278);
    $reverse = Quest::haversineDistance(51.5074, -0.1278, 55.6761, 12.5683);

    expect($forward)->toBe($reverse);
});
