<?php

use App\Models\GA4Connection;
use App\Models\GA4Snapshot;
use App\Services\GA4Service;
use App\Services\GA4SnapshotService;

test('service generate creates snapshot with demographics data', function () {
    $connection = GA4Connection::factory()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $mockGA4Service->shouldReceive('getAccessTokenForConnection')
        ->once()
        ->andReturn('test-token');

    $mockGA4Service->shouldReceive('getDemographics')
        ->with($connection->property_id, 'test-token')
        ->once()
        ->andReturn([
            'devices' => [['category' => 'desktop', 'sessions' => 500, 'percentage' => 50.0]],
            'channels' => [['channel' => 'Organic', 'sessions' => 500, 'conversions' => 25, 'conversionRate' => 5.0]],
            'totals' => ['sessions' => 1000, 'conversions' => 50, 'conversionRate' => 5.0],
            'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
        ]);

    $service = new GA4SnapshotService($mockGA4Service);

    $snapshot = $service->generate($connection);

    expect($snapshot)->toBeInstanceOf(GA4Snapshot::class);
    expect($snapshot->connection_id)->toBe($connection->id);
    expect($snapshot->snapshot_data)->toHaveKey('devices');
    expect($snapshot->snapshot_data)->toHaveKey('channels');
    expect($snapshot->snapshot_data)->toHaveKey('totals');
});

test('service generate marks report as generated', function () {
    $connection = GA4Connection::factory()->canGenerateReport()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $mockGA4Service->shouldReceive('getAccessTokenForConnection')->andReturn('test-token');
    $mockGA4Service->shouldReceive('getDemographics')->andReturn([
        'devices' => [],
        'channels' => [],
        'totals' => ['sessions' => 0, 'conversions' => 0, 'conversionRate' => 0.0],
        'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
    ]);

    $service = new GA4SnapshotService($mockGA4Service);
    $service->generate($connection);

    expect($connection->fresh()->canGenerateReport())->toBeFalse();
});

test('service generate sets expires_at to 7 days from now', function () {
    $connection = GA4Connection::factory()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $mockGA4Service->shouldReceive('getAccessTokenForConnection')->andReturn('test-token');
    $mockGA4Service->shouldReceive('getDemographics')->andReturn([
        'devices' => [],
        'channels' => [],
        'totals' => ['sessions' => 0, 'conversions' => 0, 'conversionRate' => 0.0],
        'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
    ]);

    $service = new GA4SnapshotService($mockGA4Service);
    $snapshot = $service->generate($connection);

    expect($snapshot->expires_at->isBetween(now()->addDays(6), now()->addDays(8)))->toBeTrue();
});

test('service get latest snapshot returns most recent', function () {
    $connection = GA4Connection::factory()->create();

    GA4Snapshot::factory()->create([
        'connection_id' => $connection->id,
        'generated_at' => now()->subDays(2),
    ]);

    $latest = GA4Snapshot::factory()->create([
        'connection_id' => $connection->id,
        'generated_at' => now(),
    ]);

    $mockGA4Service = $this->mock(GA4Service::class);
    $service = new GA4SnapshotService($mockGA4Service);

    $result = $service->getLatestSnapshot($connection);

    expect($result->id)->toBe($latest->id);
});

test('service get latest snapshot returns null when none exist', function () {
    $connection = GA4Connection::factory()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $service = new GA4SnapshotService($mockGA4Service);

    $result = $service->getLatestSnapshot($connection);

    expect($result)->toBeNull();
});
