<?php

namespace Airtable\Client\Auth;

final readonly class AirtableCredentials
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {}
}
