<?php

namespace Airtable\Client\Resources;

/**
 * @property-read string|null $id
 * @property-read string|null $name
 * @property-read string|null $description
 * @property-read string|null $primaryFieldId
 * @property-read array<int, object>|null $fields
 * @property-read array<int, object>|null $views
 */
final readonly class Table extends RawObjectResource {}
