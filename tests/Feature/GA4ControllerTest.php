<?php

use App\Models\GA4Connection;
use App\Models\User;
use App\Services\GA4Service;

test('guests cannot access ga4 index page', function () {
    $this->get(route('ga4.index'))->assertRedirect(route('login'));
});

test('authenticated users can view ga4 index page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('ga4.index'))->assertOk();
});

test('ga4 index shows connect button when no connection exists', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('ga4.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ga4/index')
        ->where('connection', null)
    );
});

test('ga4 index shows connection details when connected', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->create([
        'user_id' => $user->id,
        'property_name' => 'My Test Property',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('ga4.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ga4/index')
        ->has('connection')
        ->where('connection.property_name', 'My Test Property')
    );
});

test('connect redirects to google oauth', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('ga4.connect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('callback with error redirects with error message', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('ga4.callback', ['error' => 'access_denied']));

    $response->assertRedirect(route('ga4.index'));
    $response->assertSessionHas('error');
});

test('callback without code redirects with error message', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('ga4.callback'));

    $response->assertRedirect(route('ga4.index'));
    $response->assertSessionHas('error');
});

test('select property requires session tokens', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('ga4.select-property'));

    $response->assertRedirect(route('ga4.connect'));
});

test('select property page shows properties when tokens exist', function () {
    $this->actingAs(User::factory()->create());

    $mockService = $this->mock(GA4Service::class);
    $mockService->shouldReceive('getProperties')
        ->once()
        ->andReturn([
            [
                'property_id' => 'properties/123456789',
                'property_name' => 'Test Property',
                'account_name' => 'Test Account',
            ],
        ]);

    $response = $this->withSession([
        'ga4_access_token' => 'test-access-token',
        'ga4_refresh_token' => 'test-refresh-token',
    ])->get(route('ga4.select-property'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ga4/select-property')
        ->has('properties', 1)
        ->where('properties.0.property_name', 'Test Property')
    );
});

test('store property validates required fields', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->withSession([
        'ga4_refresh_token' => 'test-refresh-token',
    ])->post(route('ga4.store-property'), []);

    $response->assertSessionHasErrors(['property_id', 'property_name']);
});

test('store property validates property id format', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->withSession([
        'ga4_refresh_token' => 'test-refresh-token',
    ])->post(route('ga4.store-property'), [
        'property_id' => 'invalid-format',
        'property_name' => 'Test Property',
    ]);

    $response->assertSessionHasErrors(['property_id']);
});

test('store property creates connection and clears session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->withSession([
        'ga4_access_token' => 'test-access-token',
        'ga4_refresh_token' => 'test-refresh-token',
    ])->post(route('ga4.store-property'), [
        'property_id' => 'properties/123456789',
        'property_name' => 'Test Property',
    ]);

    $response->assertRedirect(route('ga4.index'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('ga4_access_token');
    $response->assertSessionMissing('ga4_refresh_token');

    $this->assertDatabaseHas('ga4_connections', [
        'user_id' => $user->id,
        'property_id' => 'properties/123456789',
        'property_name' => 'Test Property',
    ]);
});

test('store property replaces existing connection', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->create([
        'user_id' => $user->id,
        'property_id' => 'properties/111111111',
    ]);

    $this->actingAs($user);

    $this->withSession([
        'ga4_refresh_token' => 'test-refresh-token',
    ])->post(route('ga4.store-property'), [
        'property_id' => 'properties/222222222',
        'property_name' => 'New Property',
    ]);

    $this->assertDatabaseCount('ga4_connections', 1);
    $this->assertDatabaseHas('ga4_connections', [
        'user_id' => $user->id,
        'property_id' => 'properties/222222222',
    ]);
});

test('disconnect removes connection', function () {
    $user = User::factory()->create();
    GA4Connection::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = $this->delete(route('ga4.disconnect'));

    $response->assertRedirect(route('ga4.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('ga4_connections', [
        'user_id' => $user->id,
    ]);
});

test('disconnect with no connection does not error', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->delete(route('ga4.disconnect'));

    $response->assertRedirect(route('ga4.index'));
    $response->assertSessionHas('success');
});
