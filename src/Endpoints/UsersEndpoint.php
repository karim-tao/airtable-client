<?php

namespace Airtable\Client\Endpoints;

use Airtable\Client\Resources\User;

final class UsersEndpoint extends BaseEndpoint
{
    /**
     * @link https://airtable.com/developers/web/api/get-user-id-scopes
     */
    public function me(): User
    {
        return new User($this->request('GET', '/v0/meta/whoami'));
    }
}
