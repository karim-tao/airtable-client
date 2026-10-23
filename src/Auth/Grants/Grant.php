<?php

namespace Airtable\Client\Auth\Grants;

interface Grant
{
    /**
     * @return array<string, string>
     */
    public function parameters(): array;
}
