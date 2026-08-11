<?php

namespace Tests\Unit\Features\InventorySynchronization;

use App\Features\InventorySynchronization\Data\TracezillaInventoryData;
use App\Features\InventorySynchronization\Mappers\TracezillaInventoryToShopifyQuantityMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TracezillaInventoryToShopifyQuantityMapperTest extends TestCase
{
    public function test_it_converts_both_tracezilla_quantity_types_to_shopify_units(): void
    {
        $quantity = (new TracezillaInventoryToShopifyQuantityMapper)->map(
            new TracezillaInventoryData(
                sku: 'BANANA-001',
                traceable: true,
                traceableQuantityAvailable: 2,
                nonTraceableQuantityAvailable: 3,
                defaultUomConversion: 6,
                nonTraceableUomConversion: 2,
            )
        );

        $this->assertSame(18, $quantity);
    }

    public function test_it_rejects_a_fractional_shopify_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TracezillaInventoryToShopifyQuantityMapper)->map(
            new TracezillaInventoryData(
                sku: 'BANANA-001',
                traceable: false,
                traceableQuantityAvailable: 0,
                nonTraceableQuantityAvailable: 1,
                defaultUomConversion: 1,
                nonTraceableUomConversion: 0.5,
            )
        );
    }
}
