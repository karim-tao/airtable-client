<?php

use Airtable\Client\AirtableClient;
use Airtable\Client\Factory;
use Airtable\Client\Tests\Support\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

pest()->extend(TestCase::class)->in('Unit');

/**
 * @param  array<string, string|array<int, string>>  $headers
 */
function httpResponse(int $status, array $headers, string $body): Response
{
    return new Response($status, $headers, $body);
}

function tokenResponse(string $accessToken = 'new-access', string $refreshToken = 'new-refresh'): Response
{
    return new Response(200, [], (string) json_encode([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer',
        'scope' => 'data.records:read schema.bases:read',
        'expires_in' => 3600,
        'refresh_expires_in' => 5184000,
    ]));
}

function airtableFactory(FakeHttpClient $http): Factory
{
    $factory = new Psr17Factory;

    return AirtableClient::factory()
        ->withClientCredentials('client-id', 'client-secret')
        ->withHttpClient($http)
        ->withHttpFactories($factory, $factory, $factory);
}

/**
 * @param  array<int, Response>  $responses
 * @return array{0: AirtableClient, 1: FakeHttpClient}
 */
function airtableClientWith(array $responses, int $expiresAt = PHP_INT_MAX): array
{
    $http = new FakeHttpClient($responses);

    return [
        airtableFactory($http)
            ->withToken('stored-access', $expiresAt, 'stored-refresh')
            ->make(),
        $http,
    ];
}
