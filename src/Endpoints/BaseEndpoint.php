<?php

namespace Airtable\Client\Endpoints;

use Airtable\Client\AirtableClient;

abstract class BaseEndpoint
{
    final public function __construct(protected readonly AirtableClient $client) {}

    /**
     * @param  array<string, mixed>  $options
     */
    protected function request(string $method, string $uri, array $options = []): mixed
    {
        return $this->client->send($method, $uri, $options)->body();
    }
}
