<?php

namespace App\Features\OrderSynchronization\Data;

final readonly class TracezillaOrderContextData
{
    public function __construct(
        public string $customerPartnerId,
        public string $customerLocationId,
        public int $ownerId,
        public string $warehousePartnerId,
        public string $warehouseLocationId,
    ) {}
}
