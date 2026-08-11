<?php

namespace App\Features\InventorySynchronization\Mappers;

use App\Features\InventorySynchronization\Data\TracezillaInventoryData;
use InvalidArgumentException;

final readonly class TracezillaInventoryToShopifyQuantityMapper
{
    public function map(TracezillaInventoryData $inventory): int
    {
        /*
         * Demonstration mapping only. The developer must decide how Tracezilla
         * units, conversions, traceability and Shopify sellable units relate
         * for the customer's business.
         */
        $quantity =
            ($inventory->traceableQuantityAvailable * $inventory->defaultUomConversion)
            + ($inventory->nonTraceableQuantityAvailable * $inventory->nonTraceableUomConversion);

        if (! is_finite($quantity) || $quantity < 0 || floor($quantity) !== $quantity) {
            throw new InvalidArgumentException(
                "Mapped Shopify quantity for SKU [{$inventory->sku}] must be a non-negative whole number."
            );
        }

        return (int) $quantity;
    }
}
