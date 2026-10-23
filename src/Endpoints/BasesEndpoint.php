<?php

namespace Airtable\Client\Endpoints;

use Airtable\Client\Resources\Base;
use Airtable\Client\Resources\Table;
use stdClass;

final class BasesEndpoint extends BaseEndpoint
{
    /**
     * @link https://airtable.com/developers/web/api/list-bases
     *
     * @return array<int, Base>
     */
    public function all(): array
    {
        $bases = [];
        $offset = null;

        do {
            $body = $this->request('GET', '/v0/meta/bases', [
                'query' => $offset === null ? [] : ['offset' => $offset],
            ]);

            foreach ($body->bases as $base) {
                $bases[] = new Base($base);
            }

            $offset = $body->offset ?? null;
        } while ($offset !== null);

        return $bases;
    }

    /**
     * @link https://airtable.com/developers/web/api/get-base-schema
     *
     * @return array<int, Table>
     */
    public function tables(string $baseId): array
    {
        $body = $this->request('GET', '/v0/meta/bases/' . $baseId . '/tables');

        return array_map(fn (stdClass $table): Table => new Table($table), $body->tables);
    }
}
