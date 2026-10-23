<?php

use Airtable\Client\AirtableResponse;
use Airtable\Client\Endpoints\BasesEndpoint;
use Airtable\Client\Endpoints\UsersEndpoint;
use Airtable\Client\Exceptions\AirtableRequestException;
use Airtable\Client\Tests\Support\FakeHttpClient;

test('it exposes the users and bases endpoints', function () {
    [$client] = airtableClientWith([]);

    expect($client->users())->toBeInstanceOf(UsersEndpoint::class)
        ->and($client->bases())->toBeInstanceOf(BasesEndpoint::class);
});

test('send calls the airtable api with the bearer token', function () {
    [$client, $http] = airtableClientWith([httpResponse(200, ['X-Test' => 'yes'], '{"records":[]}')]);

    $response = $client->send('GET', '/v0/app1/tbl1', ['query' => ['pageSize' => 100]]);

    expect($response)->toBeInstanceOf(AirtableResponse::class)
        ->and($response->statusCode())->toBe(200)
        ->and($response->headers()['X-Test'])->toBe(['yes'])
        ->and($response->body()->records)->toBe([])
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.airtable.com/v0/app1/tbl1?pageSize=100')
        ->and($http->requests[0]->getHeaderLine('Authorization'))->toBe('Bearer stored-access')
        ->and($http->requests[0]->getHeaderLine('Accept'))->toBe('application/json');
});

test('send posts json bodies', function () {
    [$client, $http] = airtableClientWith([httpResponse(200, [], '{"records":[]}')]);

    $client->send('POST', '/v0/app1/tbl1', ['json' => ['records' => []]]);

    expect($http->requests[0]->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string) $http->requests[0]->getBody())->toBe('{"records":[]}');
});

test('send refreshes the token once when airtable rejects it', function () {
    [$client, $http] = airtableClientWith([
        httpResponse(401, [], '{"error":{"type":"AUTHENTICATION_REQUIRED","message":"Authentication required"}}'),
        tokenResponse(),
        httpResponse(200, [], '{"id":"usr123"}'),
    ]);

    $client->send('GET', '/v0/meta/whoami');

    expect($http->requests[2]->getHeaderLine('Authorization'))->toBe('Bearer new-access')
        ->and($client->token()?->refreshToken())->toBe('new-refresh');
});

test('send refreshes an expired token before calling airtable', function () {
    [$client, $http] = airtableClientWith([tokenResponse(), httpResponse(200, [], '{"id":"usr123"}')], expiresAt: time() - 10);

    $client->send('GET', '/v0/meta/whoami');

    expect((string) $http->requests[0]->getUri())->toBe('https://airtable.com/oauth2/v1/token')
        ->and($http->requests[1]->getHeaderLine('Authorization'))->toBe('Bearer new-access');
});

test('send throws the message of an airtable api error', function () {
    [$client] = airtableClientWith([httpResponse(403, [], '{"error":{"type":"INVALID_PERMISSIONS_OR_MODEL_NOT_FOUND","message":"Invalid permissions"}}')]);

    expect(fn () => $client->send('POST', '/v0/meta/bases'))
        ->toThrow(AirtableRequestException::class, 'Airtable request failed with status 403: Invalid permissions');
});

test('send throws the type of an airtable api error without a message', function () {
    [$client] = airtableClientWith([httpResponse(422, [], '{"error":{"type":"INVALID_REQUEST_UNKNOWN"}}')]);

    expect(fn () => $client->send('GET', '/v0/app1/tbl1'))
        ->toThrow(AirtableRequestException::class, 'Airtable request failed with status 422: INVALID_REQUEST_UNKNOWN');
});

test('send throws a plain airtable error code', function () {
    [$client] = airtableClientWith([httpResponse(404, [], '{"error":"NOT_FOUND"}')]);

    expect(fn () => $client->send('GET', '/v0/app1/tbl1'))
        ->toThrow(AirtableRequestException::class, 'Airtable request failed with status 404: NOT_FOUND');
});

test('send throws the description of an oauth error', function () {
    [$client] = airtableClientWith([httpResponse(400, [], '{"error":"invalid_grant","error_description":"The refresh token is invalid"}')], expiresAt: time() - 10);

    expect(fn () => $client->send('GET', '/v0/meta/whoami'))
        ->toThrow(AirtableRequestException::class, 'Airtable request failed with status 400: The refresh token is invalid');
});

test('send keeps a non json error body', function () {
    [$client] = airtableClientWith([httpResponse(503, [], '<html>Unavailable</html>')]);

    expect(fn () => $client->send('GET', '/v0/meta/bases'))->toThrow(function (AirtableRequestException $exception) {
        expect($exception->statusCode())->toBe(503)
            ->and($exception->responseBody())->toBe('<html>Unavailable</html>')
            ->and($exception->getMessage())->toBe('Airtable request failed with status 503.');
    });
});

test('send returns null for an empty body', function () {
    [$client] = airtableClientWith([httpResponse(204, [], '')]);

    expect($client->send('DELETE', '/v0/app1/tbl1/rec1')->body())->toBeNull();
});

test('send rejects a successful body that is not json', function () {
    [$client] = airtableClientWith([httpResponse(200, [], 'not json')]);

    expect(fn () => $client->send('GET', '/v0/meta/whoami'))
        ->toThrow(RuntimeException::class, 'Airtable response body is not valid JSON.');
});

test('authenticate returns the current token', function () {
    $http = new FakeHttpClient([tokenResponse()]);

    $token = airtableFactory($http)
        ->withAuthorizationCodeAuth('the-code', 'https://app.test/callback', 'the-verifier')
        ->make()
        ->authenticate();

    expect($token->accessToken())->toBe('new-access');
});
