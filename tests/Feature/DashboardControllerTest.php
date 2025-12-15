<?php

use App\Models\GA4Connection;
use App\Models\GA4Snapshot;
use App\Models\User;
use App\Services\GA4Service;

test('guests cannot access dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can view dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});

test('dashboard shows empty state when no connection', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('connection', null)
        ->where('snapshot', null)
    );
});

test('dashboard shows connection without snapshot', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->create([
        'user_id' => $user->id,
        'property_name' => 'Test Property',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('connection')
        ->where('connection.property_name', 'Test Property')
        ->where('snapshot', null)
    );
});

test('dashboard shows latest snapshot', function () {
    $user = User::factory()->create();
    $connection = GA4Connection::factory()->create(['user_id' => $user->id]);
    GA4Snapshot::factory()->create(['connection_id' => $connection->id]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('connection')
        ->has('snapshot')
        ->has('snapshot.data')
        ->has('snapshot.generated_at')
    );
});

test('dashboard shows rate limit status', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->recentlyGeneratedReport()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('connection.can_generate_report', false)
        ->has('connection.time_until_next_report')
    );
});

test('guests cannot generate snapshot', function () {
    $this->post(route('dashboard.generate'))->assertRedirect(route('login'));
});

test('generate snapshot requires connection', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('dashboard.generate'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('generate snapshot respects rate limit', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->recentlyGeneratedReport()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = $this->post(route('dashboard.generate'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('generate snapshot creates new snapshot', function () {
    $user = User::factory()->create();
    $connection = GA4Connection::factory()->canGenerateReport()->create(['user_id' => $user->id]);

    $mockService = $this->mock(GA4Service::class);
    $mockService->shouldReceive('getAccessTokenForConnection')->once()->andReturn('test-token');
    $mockService->shouldReceive('getAllReports')->once()->andReturn([
        'devices' => [['category' => 'desktop', 'sessions' => 100, 'percentage' => 100.0]],
        'channels' => [['channel' => 'Direct', 'sessions' => 100, 'conversions' => 5, 'conversionRate' => 5.0]],
        'totals' => ['sessions' => 100, 'conversions' => 5, 'conversionRate' => 5.0],
        'dateRange' => ['startDate' => '2025-11-15', 'endDate' => '2025-12-15'],
        'funnel' => [],
        'exitRates' => [],
        'scrollDepth' => [],
        'landingPages' => ['desktop' => [], 'mobile' => []],
        'productPages' => ['purchasers' => ['avgDuration' => 0.0, 'sessions' => 0], 'nonPurchasers' => ['avgDuration' => 0.0, 'sessions' => 0]],
        'pagesBuckets' => [],
    ]);

    $this->actingAs($user);

    $response = $this->post(route('dashboard.generate'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('ga4_snapshots', [
        'connection_id' => $connection->id,
    ]);
});

test('generate snapshot updates rate limit timestamp', function () {
    $user = User::factory()->create();
    $connection = GA4Connection::factory()->canGenerateReport()->create(['user_id' => $user->id]);

    $mockService = $this->mock(GA4Service::class);
    $mockService->shouldReceive('getAccessTokenForConnection')->once()->andReturn('test-token');
    $mockService->shouldReceive('getAllReports')->once()->andReturn([
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

    $this->actingAs($user);

    $this->post(route('dashboard.generate'));

    expect($connection->fresh()->canGenerateReport())->toBeFalse();
});

test('generate snapshot handles api errors gracefully', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->canGenerateReport()->create(['user_id' => $user->id]);

    $mockService = $this->mock(GA4Service::class);
    $mockService->shouldReceive('getAccessTokenForConnection')->once()->andThrow(new \Exception('API Error'));

    $this->actingAs($user);

    $response = $this->post(route('dashboard.generate'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});
