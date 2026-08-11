<?php

namespace App\Features\InventorySynchronization\Options;

use InvalidArgumentException;

final readonly class InventorySyncOptions
{
    public function __construct(
        public bool $dryRun = true,
        public ?int $limit = null,
    ) {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Inventory synchronization limit must be positive.');
        }
    }

    public function willExecute(): bool
    {
        return ! $this->dryRun;
    }
}
