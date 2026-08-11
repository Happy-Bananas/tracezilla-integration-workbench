<?php

namespace App\Features\CatalogSynchronization\Options;

use InvalidArgumentException;

final readonly class CatalogSyncOptions
{
    public function __construct(
        public bool $dryRun = true,
        public ?int $limit = null,
    ) {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException(
                'Catalog synchronization limit must be a positive integer.'
            );
        }
    }

    public static function dryRun(?int $limit = null): self
    {
        return new self(
            dryRun: true,
            limit: $limit,
        );
    }

    public static function execute(?int $limit = null): self
    {
        return new self(
            dryRun: false,
            limit: $limit,
        );
    }

    public function willExecute(): bool
    {
        return ! $this->dryRun;
    }
}
