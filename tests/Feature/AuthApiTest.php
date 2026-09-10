<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('registers a user and returns a token', function () {
    $payload = [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->postJson('/api/register', $payload)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Registered successfully')
        ->assertJsonPath('data.user.email', 'ada@example.com')
        ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'token']]);

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'email' => 'ada@example.com',
        'name' => 'Ada Lovelace',
    ]);
});

it('validates required fields when registering', function () {
    $this->postJson('/api/register', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('logs in a user and returns a token', function () {
    $user = User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Logged in successfully')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['user', 'token']]);
});

it('rejects invalid login credentials', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid credentials');
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('logs out and revokes the current token', function () {
    $user = User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password',
    ]);

    $token = $this->postJson('/api/login', [
        'email' => 'ada@example.com',
        'password' => 'password',
    ])->json('data.token');

    expect($token)->toBeString()->not->toBeEmpty();
    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Logged out successfully');

    $this->assertDatabaseCount('personal_access_tokens', 0);

    // Clear cached request-guard user so the next call re-validates the bearer token.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

it('requires authentication for the user endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
