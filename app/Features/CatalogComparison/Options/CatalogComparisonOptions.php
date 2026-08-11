<?php

namespace App\Features\CatalogComparison\Options;

use InvalidArgumentException;

final readonly class CatalogComparisonOptions
{
    public function __construct(public ?int $limit = null)
    {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Catalog comparison limit must be a positive integer.');
        }
    }
}
