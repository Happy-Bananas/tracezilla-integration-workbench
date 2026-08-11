<?php

namespace App\Features\InventorySynchronization\Results;

final readonly class InventorySyncItemResult
{
    public function __construct(
        public string $sku,
        public InventorySyncStatus $status,
        public string $message,
        public ?int $from = null,
        public ?int $to = null,
    ) {}
}
