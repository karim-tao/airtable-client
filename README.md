# Airtable Client

[![Tests](https://github.com/karim-tao/airtable-client/actions/workflows/tests.yml/badge.svg)](https://github.com/karim-tao/airtable-client/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/karim-tao/airtable-client)](https://packagist.org/packages/karim-tao/airtable-client)
[![License](https://img.shields.io/packagist/l/karim-tao/airtable-client)](https://packagist.org/packages/karim-tao/airtable-client)

A framework-agnostic PHP client for the Airtable API, built on PSR-18, bring your own HTTP client, everything else is wired for you.

In this example, we build a client from a stored token and list the bases of the authorized user, the token is refreshed for you:

```php
use Airtable\Client\AirtableClient;

$airtable = AirtableClient::factory()
    ->withClientCredentials($clientId, $clientSecret)
    ->withToken($accessToken, $expiresAt, $refreshToken)
    ->make();

$bases = $airtable->bases()->all();
```

> Using Laravel? See [`karim-tao/airtable-laravel`](https://github.com/karim-tao/airtable-laravel).

## Installation

> Requires PHP 8.3+.

You can install the package via composer:

```bash
composer require karim-tao/airtable-client
```

Ensure the [`php-http/discovery`](https://github.com/php-http/discovery) composer plugin is allowed to run, or install a [PSR-18](https://www.php-fig.org/psr/psr-18/) client manually if your project does not already have one:

```bash
composer require guzzlehttp/guzzle
```

## Usage

### Creating a client

```php
use Airtable\Client\AirtableClient;

$airtable = AirtableClient::factory()
    ->withClientCredentials($clientId, $clientSecret)                  // required
    ->withAuthorizationCodeAuth($code, $redirectUri, $codeVerifier)    // required (see Authentication for more details)
    ->withBaseUrl('https://api.airtable.com')                          // optional
    ->withTokenUrl($tokenUrl)                                          // optional
    ->withEventDispatcher($events)                                     // optional (PSR-14)
    ->withHttpClient($client)                                          // optional (PSR-18)
    ->make();
```

### Authentication

A client always needs your Airtable OAuth integration credentials plus one authentication method, slotted into the builder above.
The token manager exchanges the code and refreshes the token for you.

| Method             | Builder call                                                      | Use when                                 |
|--------------------|-------------------------------------------------------------------|------------------------------------------|
| Authorization code | `->withAuthorizationCodeAuth($code, $redirectUri, $codeVerifier)` | right after the user authorised your app |
| Token              | `->withToken($accessToken, $expiresAt, $refreshToken)`            | reuse a token you stored                 |

Airtable requires PKCE: send the user to `https://airtable.com/oauth2/v1/authorize` with a `code_challenge` (the base64url SHA-256 of a random `code_verifier`, method `S256`), then pass the same verifier with the code.

Read (and persist) the current token:

```php
$token = $airtable->authenticate();
$token = $airtable->token();

$token->accessToken();
$token->refreshToken();
$token->expiresAt();
```

### Endpoints

```php
// Users
$user = $airtable->users()->me();

// Bases
$bases = $airtable->bases()->all();
$tables = $airtable->bases()->tables($baseId);
```

`bases()->all()` walks every page of bases for you, following the `offset` Airtable returns.

### Events

With an event dispatcher, the client dispatches `Airtable\Client\Events\TokenRefreshed` whenever it obtains a token: on the code exchange and on every refresh. Airtable invalidates the previous pair on every refresh, so listen to it to store the new pair as soon as Airtable issues it:

```php
$event->token->accessToken();
$event->token->refreshToken();
$event->token->expiresAt();
```

### Raw requests

For anything not covered by an endpoint, send a request directly:

```php
$response = $airtable->send('GET', '/v0/' . $baseId . '/' . $tableId, ['query' => ['pageSize' => 100]]);

$response->statusCode();
$response->body();
$response->headers();
```

## How it works

`AirtableClient` wraps a transporter over your PSR-18 client (or one discovered via `php-http/discovery`) and a token manager that:

- exchanges the authorization code with its PKCE verifier, or reuses a stored token, authenticating with HTTP Basic on `https://airtable.com/oauth2/v1/token`;
- refreshes the token automatically when it expires, and once more when Airtable answers 401;
- dispatches `TokenRefreshed` through the PSR-14 event dispatcher, when you pass one, every time it obtains a token.

Every endpoint is a small typed wrapper over `send()`, which calls `https://api.airtable.com`, or the base URL of the factory.

## Testing

```bash
composer test
```

To test code that uses the client without hitting Airtable, inject a stub PSR-18 client:

```php
$airtable = AirtableClient::factory()
    ->withClientCredentials('id', 'secret')
    ->withToken('access', time() + 3600, 'refresh')
    ->withHttpClient($stubPsr18Client)
    ->make();
```

## License

Airtable Client is open-sourced software licensed under the [MIT license](LICENSE.md).
