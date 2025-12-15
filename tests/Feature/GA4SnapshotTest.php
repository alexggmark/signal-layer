<?php

use App\Models\GA4Connection;
use App\Models\GA4Snapshot;

test('ga4 snapshot belongs to a connection', function () {
    $connection = GA4Connection::factory()->create();
    $snapshot = GA4Snapshot::factory()->create(['connection_id' => $connection->id]);

    expect($snapshot->connection->id)->toBe($connection->id);
});

test('snapshot_data is cast to array', function () {
    $snapshot = GA4Snapshot::factory()->create();

    expect($snapshot->snapshot_data)->toBeArray();
    expect($snapshot->snapshot_data)->toHaveKey('devices');
    expect($snapshot->snapshot_data)->toHaveKey('channels');
    expect($snapshot->snapshot_data)->toHaveKey('totals');
});

test('generated_at is cast to datetime', function () {
    $snapshot = GA4Snapshot::factory()->create();

    expect($snapshot->generated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('expires_at is cast to datetime', function () {
    $snapshot = GA4Snapshot::factory()->create();

    expect($snapshot->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('is expired returns true when past expires_at', function () {
    $snapshot = GA4Snapshot::factory()->expired()->create();

    expect($snapshot->isExpired())->toBeTrue();
});

test('is expired returns false when before expires_at', function () {
    $snapshot = GA4Snapshot::factory()->create();

    expect($snapshot->isExpired())->toBeFalse();
});

test('is expired returns false when expires_at is null', function () {
    $snapshot = GA4Snapshot::factory()->create(['expires_at' => null]);

    expect($snapshot->isExpired())->toBeFalse();
});

test('connection has snapshots relationship', function () {
    $connection = GA4Connection::factory()->create();
    GA4Snapshot::factory()->count(3)->create(['connection_id' => $connection->id]);

    expect($connection->snapshots)->toHaveCount(3);
});

test('connection has latest snapshot relationship', function () {
    $connection = GA4Connection::factory()->create();
    GA4Snapshot::factory()->create([
        'connection_id' => $connection->id,
        'generated_at' => now()->subDay(),
    ]);
    $latest = GA4Snapshot::factory()->create([
        'connection_id' => $connection->id,
        'generated_at' => now(),
    ]);

    expect($connection->latestSnapshot->id)->toBe($latest->id);
});

test('deleting connection cascades to snapshots', function () {
    $connection = GA4Connection::factory()->create();
    GA4Snapshot::factory()->count(2)->create(['connection_id' => $connection->id]);

    $connection->delete();

    $this->assertDatabaseCount('ga4_snapshots', 0);
});
