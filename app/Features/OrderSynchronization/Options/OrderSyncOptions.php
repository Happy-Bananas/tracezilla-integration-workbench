<?php

namespace App\Features\OrderSynchronization\Options;

use InvalidArgumentException;

final readonly class OrderSyncOptions
{
    public function __construct(
        public bool $dryRun = true,
        public int $days = 3,
        public ?int $limit = null,
    ) {
        if ($days < 1) {
            throw new InvalidArgumentException('Order sync days must be a positive integer.');
        }

        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Order sync limit must be a positive integer.');
        }
    }

    public function willExecute(): bool
    {
        return ! $this->dryRun;
    }
}
