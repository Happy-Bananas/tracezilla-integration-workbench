<?php

namespace App\Features\OrderSynchronization\Results;

final readonly class OrderSyncItemResult
{
    public function __construct(
        public string $shopifyOrder,
        public OrderSyncStatus $status,
        public string $message,
        public ?string $externalReference = null,
    ) {}
}
