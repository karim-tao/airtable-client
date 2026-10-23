<?php

use Airtable\Client\AirtableClient;
use Airtable\Client\Events\TokenRefreshed;
use Airtable\Client\Tests\Support\EventRecorder;
use Airtable\Client\Tests\Support\FakeHttpClient;

test('make requires client credentials', function () {
    expect(fn () => AirtableClient::factory()->make())
        ->toThrow(InvalidArgumentException::class, 'Airtable client credentials are required.');
});

test('make requires an authorization code or a stored token', function () {
    expect(fn () => AirtableClient::factory()->withClientCredentials('id', 'secret')->make())
        ->toThrow(InvalidArgumentException::class, 'Airtable authentication is required.');
});

test('make builds a client using PSR discovery when no transport is injected', function () {
    $client = AirtableClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withToken('access', time() + 3600, 'refresh')
        ->make();

    expect($client)->toBeInstanceOf(AirtableClient::class)
        ->and($client->token()?->refreshToken())->toBe('refresh');
});

test('make hands the event dispatcher to the token manager', function () {
    $events = new EventRecorder;

    airtableFactory(new FakeHttpClient([tokenResponse()]))
        ->withAuthorizationCodeAuth('the-code', 'https://app.test/callback', 'the-verifier')
        ->withEventDispatcher($events)
        ->make()
        ->authenticate();

    expect($events->events)->toHaveCount(1)
        ->and($events->events[0])->toBeInstanceOf(TokenRefreshed::class);
});

test('make sends the calls to the given base url', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{"id":"usr123"}')]);

    airtableFactory($http)
        ->withToken('access', PHP_INT_MAX)
        ->withBaseUrl('https://airtable.example.test')
        ->make()
        ->users()
        ->me();

    expect((string) $http->requests[0]->getUri())->toBe('https://airtable.example.test/v0/meta/whoami');
});

test('make asks for the token at the given token url', function () {
    $http = new FakeHttpClient([tokenResponse()]);

    airtableFactory($http)
        ->withAuthorizationCodeAuth('the-code', 'https://app.test/callback', 'the-verifier')
        ->withTokenUrl('https://auth.example.test/token')
        ->make()
        ->authenticate();

    expect((string) $http->requests[0]->getUri())->toBe('https://auth.example.test/token');
});
