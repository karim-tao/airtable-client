<?php

namespace Airtable\Client;

use Airtable\Client\Auth\Token;
use Airtable\Client\Auth\TokenManager;
use Airtable\Client\Endpoints\BasesEndpoint;
use Airtable\Client\Endpoints\UsersEndpoint;
use Airtable\Client\Http\Transporter;

final class AirtableClient
{
    private function __construct(
        private readonly Transporter $transporter,
        private readonly TokenManager $tokens,
    ) {}

    public static function factory(): Factory
    {
        return new Factory(static fn (mixed ...$args): self => new self(...$args));
    }

    public function users(): UsersEndpoint
    {
        return new UsersEndpoint($this);
    }

    public function bases(): BasesEndpoint
    {
        return new BasesEndpoint($this);
    }

    public function authenticate(): Token
    {
        return $this->tokens->ensure();
    }

    public function token(): ?Token
    {
        return $this->tokens->current();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function send(string $method, string $uri, array $options = []): AirtableResponse
    {
        $token = $this->tokens->ensure();
        $response = $this->transporter->send($method, $uri, $options, $token->accessToken());

        if ($this->transporter->isExpiredTokenResponse($response)) {
            $token = $this->tokens->refresh();
            $response = $this->transporter->send($method, $uri, $options, $token->accessToken());
        }

        return $this->transporter->response($response);
    }
}
