<?php

namespace Airtable\Client\Exceptions;

use RuntimeException;

final class AirtableRequestException extends RuntimeException implements AirtableException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly mixed $responseBody,
        string $message = 'Airtable request failed.',
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function responseBody(): mixed
    {
        return $this->responseBody;
    }
}
