<?php

use App\Models\GA4Connection;
use App\Models\User;

test('ga4 connection belongs to a user', function () {
    $user = User::factory()->create();
    $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

    expect($connection->user->id)->toBe($user->id);
});

test('user can have a ga4 connection', function () {
    $user = User::factory()->create();
    $connection = GA4Connection::factory()->create(['user_id' => $user->id]);

    expect($user->ga4Connection->id)->toBe($connection->id);
});

test('refresh token is encrypted', function () {
    $connection = GA4Connection::factory()->create([
        'refresh_token' => 'test-refresh-token',
    ]);

    $rawConnection = \DB::table('ga4_connections')->find($connection->id);

    expect($rawConnection->refresh_token)->not->toBe('test-refresh-token');
    expect($connection->refresh_token)->toBe('test-refresh-token');
});

test('refresh token is hidden from serialization', function () {
    $connection = GA4Connection::factory()->create();

    expect($connection->toArray())->not->toHaveKey('refresh_token');
});

test('can generate report when no previous report generated', function () {
    $connection = GA4Connection::factory()->create([
        'last_report_generated_at' => null,
    ]);

    expect($connection->canGenerateReport())->toBeTrue();
});

test('cannot generate report within rate limit window', function () {
    $connection = GA4Connection::factory()->recentlyGeneratedReport()->create();

    expect($connection->canGenerateReport())->toBeFalse();
});

test('can generate report after rate limit window expires', function () {
    $connection = GA4Connection::factory()->canGenerateReport()->create();

    expect($connection->canGenerateReport())->toBeTrue();
});

test('time until next report returns null when can generate', function () {
    $connection = GA4Connection::factory()->create([
        'last_report_generated_at' => null,
    ]);

    expect($connection->timeUntilNextReport())->toBeNull();
});

test('time until next report returns hours and minutes when rate limited', function () {
    $connection = GA4Connection::factory()->recentlyGeneratedReport()->create();

    $timeUntil = $connection->timeUntilNextReport();

    expect($timeUntil)->toHaveKeys(['hours', 'minutes']);
    expect($timeUntil['hours'])->toBeInt();
    expect($timeUntil['minutes'])->toBeInt();
});

test('mark report generated updates timestamp', function () {
    $connection = GA4Connection::factory()->create([
        'last_report_generated_at' => null,
    ]);

    $connection->markReportGenerated();

    expect($connection->last_report_generated_at)->not->toBeNull();
    expect($connection->canGenerateReport())->toBeFalse();
});

test('connected at is cast to datetime', function () {
    $connection = GA4Connection::factory()->create();

    expect($connection->connected_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('last report generated at is cast to datetime when set', function () {
    $connection = GA4Connection::factory()->recentlyGeneratedReport()->create();

    expect($connection->last_report_generated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
