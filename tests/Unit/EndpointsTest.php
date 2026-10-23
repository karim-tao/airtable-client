<?php

use Airtable\Client\Resources\Base;
use Airtable\Client\Resources\Table;
use Airtable\Client\Resources\User;

test('users()->me fetches the authorized user and the granted scopes', function () {
    [$client, $http] = airtableClientWith([httpResponse(200, [], '{"id":"usr123","email":"karim@example.com","scopes":["schema.bases:read"]}')]);

    $user = $client->users()->me();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBe('usr123')
        ->and($user->email)->toBe('karim@example.com')
        ->and($user->scopes)->toBe(['schema.bases:read'])
        ->and(isset($user->email))->toBeTrue()
        ->and($user->raw()->email)->toBe('karim@example.com')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.airtable.com/v0/meta/whoami');
});

test('bases()->all walks every page of bases with the offset', function () {
    [$client, $http] = airtableClientWith([
        httpResponse(200, [], '{"bases":[{"id":"app1","name":"CRM","permissionLevel":"create"}],"offset":"itr2"}'),
        httpResponse(200, [], '{"bases":[{"id":"app2","name":"Projects","permissionLevel":"read"}]}'),
    ]);

    $bases = $client->bases()->all();

    expect($bases)->toHaveCount(2)
        ->and($bases[0])->toBeInstanceOf(Base::class)
        ->and($bases[0]->name)->toBe('CRM')
        ->and($bases[1]->permissionLevel)->toBe('read')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.airtable.com/v0/meta/bases')
        ->and((string) $http->requests[1]->getUri())->toBe('https://api.airtable.com/v0/meta/bases?offset=itr2');
});

test('bases()->tables fetches the schema of a base', function () {
    [$client, $http] = airtableClientWith([httpResponse(200, [], '{"tables":[{"id":"tbl1","name":"Contacts","primaryFieldId":"fld1","fields":[{"id":"fld1","name":"Name","type":"singleLineText"}],"views":[]}]}')]);

    $tables = $client->bases()->tables('app1');

    expect($tables)->toHaveCount(1)
        ->and($tables[0])->toBeInstanceOf(Table::class)
        ->and($tables[0]->name)->toBe('Contacts')
        ->and($tables[0]->raw()->fields[0]->type)->toBe('singleLineText')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.airtable.com/v0/meta/bases/app1/tables');
});
