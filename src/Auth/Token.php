<?php

namespace Airtable\Client\Auth;

use Airtable\Client\Exceptions\AirtableAuthenticationException;

final readonly class Token
{
    public function __construct(
        private string $accessToken,
        private int $expiresAt,
        private ?string $refreshToken = null,
    ) {}

    public static function fromOAuthResponse(mixed $response): self
    {
        $accessToken = is_object($response) ? ($response->access_token ?? null) : null;
        $expiresIn = is_object($response) ? ($response->expires_in ?? null) : null;
        $refreshToken = is_object($response) ? ($response->refresh_token ?? null) : null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new AirtableAuthenticationException('Airtable authentication response did not include an access token.');
        }

        if (! is_numeric($expiresIn) || (int) $expiresIn < 1) {
            throw new AirtableAuthenticationException('Airtable authentication response did not include a valid expiration.');
        }

        return new self(
            $accessToken,
            time() + (int) $expiresIn,
            is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null,
        );
    }

    public static function fromValues(string $accessToken, int $expiresAt, ?string $refreshToken = null): self
    {
        return new self($accessToken, $expiresAt, $refreshToken);
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function refreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function expiresAt(): int
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= time();
    }
}
