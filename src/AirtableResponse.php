<?php

namespace Airtable\Client;

final readonly class AirtableResponse
{
    /**
     * @param  array<string, array<int, string>>  $headers
     */
    public function __construct(
        private mixed $body,
        private int $statusCode,
        private array $headers = [],
    ) {}

    public function body(): mixed
    {
        return $this->body;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
