<?php

namespace Tests\Unit\Features\InventorySynchronization;

use App\Features\InventorySynchronization\Actions\SynchronizeInventory;
use App\Features\InventorySynchronization\Data\ShopifyInventoryItemData;
use App\Features\InventorySynchronization\Data\TracezillaInventoryData;
use App\Features\InventorySynchronization\Mappers\TracezillaInventoryToShopifyQuantityMapper;
use App\Features\InventorySynchronization\Options\InventorySyncOptions;
use App\Features\InventorySynchronization\Results\InventorySyncStatus;
use App\Services\ShopifyInventoryService;
use App\Services\TracezillaInventoryService;
use PHPUnit\Framework\TestCase;

class SynchronizeInventoryTest extends TestCase
{
    public function test_dry_run_reports_change_without_writing_to_shopify(): void
    {
        $tracezilla = $this->createMock(TracezillaInventoryService::class);
        $tracezilla->method('getWarehouseInventory')->willReturn([
            $this->tracezillaInventory('BANANA-001', 7),
        ]);

        $shopify = $this->createMock(ShopifyInventoryService::class);
        $shopify->method('getInventoryBySku')->willReturn([
            'BANANA-001' => $this->shopifyInventory('BANANA-001', 2),
        ]);
        $shopify->expects($this->never())->method('setAvailable');

        $result = $this->action($tracezilla, $shopify)
            ->run(new InventorySyncOptions);

        $this->assertSame(1, $result->count(InventorySyncStatus::WouldUpdate));
        $this->assertSame(2, $result->items()[0]->from);
        $this->assertSame(7, $result->items()[0]->to);
    }

    public function test_execution_writes_only_a_changed_tracked_quantity(): void
    {
        $tracezilla = $this->createMock(TracezillaInventoryService::class);
        $tracezilla->method('getWarehouseInventory')->willReturn([
            $this->tracezillaInventory('BANANA-001', 7),
            $this->tracezillaInventory('BANANA-002', 4),
        ]);

        $changed = $this->shopifyInventory('BANANA-001', 2);
        $unchanged = $this->shopifyInventory('BANANA-002', 4);
        $shopify = $this->createMock(ShopifyInventoryService::class);
        $shopify->method('getInventoryBySku')->willReturn([
            'BANANA-001' => $changed,
            'BANANA-002' => $unchanged,
        ]);
        $shopify->expects($this->once())
            ->method('setAvailable')
            ->with(
                $changed,
                7,
                SynchronizeInventory::SHOPIFY_LOCATION_ID,
            );

        $result = $this->action($tracezilla, $shopify)
            ->run(new InventorySyncOptions(dryRun: false));

        $this->assertSame(1, $result->count(InventorySyncStatus::Updated));
        $this->assertSame(1, $result->count(InventorySyncStatus::Unchanged));
    }

    public function test_it_skips_missing_and_untracked_shopify_items(): void
    {
        $tracezilla = $this->createMock(TracezillaInventoryService::class);
        $tracezilla->method('getWarehouseInventory')->willReturn([
            $this->tracezillaInventory('MISSING', 1),
            $this->tracezillaInventory('UNTRACKED', 1),
        ]);

        $shopify = $this->createMock(ShopifyInventoryService::class);
        $shopify->method('getInventoryBySku')->willReturn([
            'UNTRACKED' => new ShopifyInventoryItemData(
                'variant-2', 'item-2', 'UNTRACKED', false, null
            ),
        ]);
        $shopify->expects($this->never())->method('setAvailable');

        $result = $this->action($tracezilla, $shopify)
            ->run(new InventorySyncOptions);

        $this->assertSame(2, $result->count(InventorySyncStatus::Skipped));
    }

    private function action(
        TracezillaInventoryService $tracezilla,
        ShopifyInventoryService $shopify,
    ): SynchronizeInventory {
        return new SynchronizeInventory(
            $tracezilla,
            $shopify,
            new TracezillaInventoryToShopifyQuantityMapper,
        );
    }

    private function tracezillaInventory(string $sku, float $quantity): TracezillaInventoryData
    {
        return new TracezillaInventoryData($sku, false, 0, $quantity, 1, 1);
    }

    private function shopifyInventory(string $sku, int $quantity): ShopifyInventoryItemData
    {
        return new ShopifyInventoryItemData(
            "variant-{$sku}",
            "item-{$sku}",
            $sku,
            true,
            $quantity,
        );
    }
}
