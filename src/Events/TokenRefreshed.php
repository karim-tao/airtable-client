<?php

namespace Airtable\Client\Events;

use Airtable\Client\Auth\Token;

final readonly class TokenRefreshed
{
    public function __construct(public Token $token) {}
}
