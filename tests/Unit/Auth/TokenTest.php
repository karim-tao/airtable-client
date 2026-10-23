<?php

use Airtable\Client\Auth\Token;
use Airtable\Client\Exceptions\AirtableAuthenticationException;

test('it reads the token and the refresh token', function () {
    $token = Token::fromOAuthResponse((object) [
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'expires_in' => 3600,
        'refresh_expires_in' => 5184000,
    ]);

    expect($token->accessToken())->toBe('access')
        ->and($token->refreshToken())->toBe('refresh')
        ->and($token->expiresAt())->toBeGreaterThan(time() + 3500)
        ->and($token->isExpired())->toBeFalse();
});

test('it rejects an incomplete authentication response', function (mixed $response, string $message) {
    expect(fn () => Token::fromOAuthResponse($response))->toThrow(AirtableAuthenticationException::class, $message);
})->with([
    'not an object' => ['nope', 'did not include an access token'],
    'no access token' => [(object) ['expires_in' => 60, 'refresh_token' => 'r'], 'did not include an access token'],
    'no expiration' => [(object) ['access_token' => 'a', 'refresh_token' => 'r'], 'did not include a valid expiration'],
]);

test('a response without a refresh token leaves it empty', function () {
    expect(Token::fromOAuthResponse((object) ['access_token' => 'a', 'expires_in' => 60, 'refresh_token' => ''])->refreshToken())->toBeNull();
});

test('a token past its expiry is expired', function () {
    expect(Token::fromValues('a', time() - 1, 'r')->isExpired())->toBeTrue();
});
