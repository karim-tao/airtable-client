<?php

namespace Airtable\Client\Auth;

use Airtable\Client\Auth\Grants\Grant;
use Airtable\Client\Auth\Grants\RefreshTokenGrant;
use Airtable\Client\Events\TokenRefreshed;
use Airtable\Client\Exceptions\AirtableAuthenticationException;
use Airtable\Client\Http\Transporter;
use Psr\EventDispatcher\EventDispatcherInterface;

final class TokenManager
{
    public function __construct(
        private readonly AirtableCredentials $credentials,
        private readonly Transporter $transporter,
        private readonly string $tokenUrl,
        private readonly ?Grant $grant = null,
        private ?Token $token = null,
        private readonly ?EventDispatcherInterface $events = null,
    ) {}

    public function ensure(): Token
    {
        if ($this->token !== null && ! $this->token->isExpired()) {
            return $this->token;
        }

        if ($this->token?->refreshToken() !== null) {
            return $this->refresh();
        }

        return $this->store($this->authenticate());
    }

    public function refresh(): Token
    {
        $refreshToken = $this->token?->refreshToken();

        return $this->store($refreshToken !== null
            ? $this->requestToken(new RefreshTokenGrant($refreshToken))
            : $this->authenticate());
    }

    public function current(): ?Token
    {
        return $this->token;
    }

    private function authenticate(): Token
    {
        if ($this->grant === null) {
            throw new AirtableAuthenticationException('Airtable cannot authenticate: no grant configured and no refreshable token.');
        }

        return $this->requestToken($this->grant);
    }

    private function requestToken(Grant $grant): Token
    {
        $response = $this->transporter->response(
            $this->transporter->send('POST', $this->tokenUrl, [
                'form_params' => $grant->parameters(),
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->credentials->clientId . ':' . $this->credentials->clientSecret),
                ],
            ])
        );

        return Token::fromOAuthResponse($response->body());
    }

    private function store(Token $token): Token
    {
        $this->token = $token;

        $this->events?->dispatch(new TokenRefreshed($token));

        return $token;
    }
}
