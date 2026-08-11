<?php

namespace App\Features\InventorySynchronization\Data;

use InvalidArgumentException;

final readonly class ShopifyInventoryItemData
{
    public function __construct(
        public string $variantId,
        public string $inventoryItemId,
        public ?string $sku,
        public bool $tracked,
        public ?int $available,
    ) {}

    public static function fromApiResponse(array $variant): self
    {
        $item = $variant['inventoryItem'] ?? [];
        $quantities = $item['inventoryLevel']['quantities'] ?? [];
        $available = collect($quantities)->firstWhere('name', 'available');

        if (empty($variant['id']) || empty($item['id'])) {
            throw new InvalidArgumentException('Shopify inventory response is missing an ID.');
        }

        return new self(
            variantId: (string) $variant['id'],
            inventoryItemId: (string) $item['id'],
            sku: isset($variant['sku']) ? trim((string) $variant['sku']) : null,
            tracked: (bool) ($item['tracked'] ?? false),
            available: isset($available['quantity']) ? (int) $available['quantity'] : null,
        );
    }
}
