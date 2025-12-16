<?php

use App\Models\GA4Connection;
use App\Models\GA4Snapshot;
use App\Services\GA4Service;
use App\Services\GA4SnapshotService;

test('service generate creates snapshot with all report data', function () {
    $connection = GA4Connection::factory()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $mockGA4Service->shouldReceive('getAccessTokenForConnection')
        ->once()
        ->andReturn('test-token');

    $mockGA4Service->shouldReceive('getAllReports')
        ->with($connection->property_id, 'test-token')
        ->once()
        ->andReturn([
            'devices' => [['category' => 'desktop', 'sessions' => 500, 'percentage' => 50.0]],
            'channels' => [['channel' => 'Organic', 'sessions' => 500, 'conversions' => 25, 'conversionRate' => 5.0]],
            'totals' => ['sessions' => 1000, 'conversions' => 50, 'conversionRate' => 5.0],
            'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
            'funnel' => [['step' => 'Session Start', 'count' => 1000, 'dropOffRate' => 0.0]],
            'exitRates' => [['page' => '/', 'bounceRate' => 20.0, 'pageviews' => 500, 'exitRate' => 20.0]],
            'scrollDepth' => [['page' => '/', 'scrolled90Percent' => 200, 'totalViews' => 500, 'scrollRate' => 40.0]],
            'landingPages' => ['desktop' => [], 'mobile' => []],
            'productPages' => ['purchasers' => ['avgDuration' => 120.0, 'sessions' => 50], 'nonPurchasers' => ['avgDuration' => 60.0, 'sessions' => 450]],
            'pagesBuckets' => [['bucket' => '1-2', 'sessions' => 350, 'conversions' => 10, 'conversionRate' => 2.86]],
        ]);

    $service = new GA4SnapshotService($mockGA4Service);

    $snapshot = $service->generate($connection);

    expect($snapshot)->toBeInstanceOf(GA4Snapshot::class);
    expect($snapshot->connection_id)->toBe($connection->id);
    expect($snapshot->snapshot_data)->toHaveKey('devices');
    expect($snapshot->snapshot_data)->toHaveKey('channels');
    expect($snapshot->snapshot_data)->toHaveKey('totals');
    expect($snapshot->snapshot_data)->toHaveKey('funnel');
    expect($snapshot->snapshot_data)->toHaveKey('exitRates');
    expect($snapshot->snapshot_data)->toHaveKey('scrollDepth');
    expect($snapshot->snapshot_data)->toHaveKey('landingPages');
    expect($snapshot->snapshot_data)->toHaveKey('productPages');
    expect($snapshot->snapshot_data)->toHaveKey('pagesBuckets');
});

test('service generate marks report as generated', function () {
    $connection = GA4Connection::factory()->canGenerateReport()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $mockGA4Service->shouldReceive('getAccessTokenForConnection')->andReturn('test-token');
    $mockGA4Service->shouldReceive('getAllReports')->andReturn([
        'devices' => [],
        'channels' => [],
        'totals' => ['sessions' => 0, 'conversions' => 0, 'conversionRate' => 0.0],
        'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
        'funnel' => [],
        'exitRates' => [],
        'scrollDepth' => [],
        'landingPages' => ['desktop' => [], 'mobile' => []],
        'productPages' => ['purchasers' => ['avgDuration' => 0.0, 'sessions' => 0], 'nonPurchasers' => ['avgDuration' => 0.0, 'sessions' => 0]],
        'pagesBuckets' => [],
    ]);

    $service = new GA4SnapshotService($mockGA4Service);
    $service->generate($connection);

    expect($connection->fresh()->canGenerateReport())->toBeFalse();
});

test('service generate sets expires_at to 7 days from now', function () {
    $connection = GA4Connection::factory()->create();

    $mockGA4Service = $this->mock(GA4Service::class);
    $mockGA4Service->shouldReceive('getAccessTokenForConnection')->andReturn('test-token');
    $mockGA4Service->shouldReceive('getAllReports')->andReturn([
        'devices' => [],
        'channels' => [],
        'totals' => ['sessions' => 0, 'conversions' => 0, 'conversionRate' => 0.0],
        'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
        'funnel' => [],
        'exitRates' => [],
        'scrollDepth' => [],
        'landingPages' => ['desktop' => [], 'mobile' => []],
        'productPages' => ['purchasers' => ['avgDuration' => 0.0, 'sessions' => 0], 'nonPurchasers' => ['avgDuration' => 0.0, 'sessions' => 0]],
        'pagesBuckets' => [],
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
