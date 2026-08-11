<?php

namespace Tests\Unit\Features\CatalogSynchronization;

use App\Features\CatalogSynchronization\Actions\SynchronizeCatalog;
use App\Features\CatalogSynchronization\Data\ShopifyVariantData;
use App\Features\CatalogSynchronization\Mappers\ShopifyVariantToTracezillaSkuMapper;
use App\Features\CatalogSynchronization\Options\CatalogSyncOptions;
use App\Features\CatalogSynchronization\Results\CatalogSyncReason;
use App\Features\CatalogSynchronization\Results\CatalogSyncStatus;
use App\Services\ShopifyCatalogService;
use App\Services\TracezillaSkuService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SynchronizeCatalogTest extends TestCase
{
    public function test_dry_run_reports_decisions_without_creating_skus(): void
    {
        $shopify = $this->createMock(ShopifyCatalogService::class);
        $shopify->method('getProductVariants')->willReturn([
            $this->variant('BANANA-EXISTING', '1'),
            $this->variant('BANANA-NEW', '2'),
            $this->variant(null, '3'),
            $this->variant('BANANA-NEW', '4'),
        ]);

        $tracezilla = $this->createMock(TracezillaSkuService::class);
        $tracezilla->method('getSkuCodes')->willReturn(['BANANA-EXISTING']);
        $tracezilla->expects($this->never())->method('createSku');

        $result = $this->action($shopify, $tracezilla)
            ->run(CatalogSyncOptions::dryRun());

        $this->assertSame(1, $result->count(CatalogSyncStatus::WouldCreate));
        $this->assertSame(2, $result->count(CatalogSyncStatus::Skipped));
        $this->assertSame(1, $result->count(CatalogSyncStatus::Invalid));
        $this->assertSame(
            CatalogSyncReason::DuplicateShopifySku,
            $result->items()[3]->reason,
        );
        $this->assertFalse($result->hasFailures());
    }

    public function test_execution_respects_the_limit_and_creates_the_selected_sku(): void
    {
        $shopify = $this->createMock(ShopifyCatalogService::class);
        $shopify->method('getProductVariants')->willReturn([
            $this->variant('BANANA-001', '1'),
            $this->variant('BANANA-002', '2'),
        ]);

        $tracezilla = $this->createMock(TracezillaSkuService::class);
        $tracezilla->method('getSkuCodes')->willReturn([]);
        $tracezilla->expects($this->once())
            ->method('createSku')
            ->willReturn(['data' => ['sku_code' => 'BANANA-001']]);

        $result = $this->action($shopify, $tracezilla)
            ->run(CatalogSyncOptions::execute(limit: 1));

        $this->assertSame(2, $result->sourceCount);
        $this->assertSame(1, $result->selectedCount);
        $this->assertSame(1, $result->count(CatalogSyncStatus::Created));
        $this->assertSame('BANANA-001', $result->items()[0]->sku);
    }

    public function test_execution_records_an_unexpected_tracezilla_failure_safely(): void
    {
        $shopify = $this->createMock(ShopifyCatalogService::class);
        $shopify->method('getProductVariants')->willReturn([
            $this->variant('BANANA-001', '1'),
        ]);

        $tracezilla = $this->createMock(TracezillaSkuService::class);
        $tracezilla->method('getSkuCodes')->willReturn([]);
        $tracezilla->method('createSku')
            ->willThrowException(new RuntimeException('Sensitive internal message.'));

        $result = $this->action($shopify, $tracezilla)
            ->run(CatalogSyncOptions::execute());

        $this->assertTrue($result->hasFailures());
        $this->assertSame(
            'An unexpected error occurred while creating the tracezilla SKU.',
            $result->items()[0]->message,
        );
        $this->assertStringNotContainsString(
            'Sensitive internal message.',
            json_encode($result->toArray()),
        );
    }

    private function action(
        ShopifyCatalogService $shopify,
        TracezillaSkuService $tracezilla,
    ): SynchronizeCatalog {
        return new SynchronizeCatalog(
            shopifyCatalog: $shopify,
            tracezillaSkus: $tracezilla,
            skuMapper: new ShopifyVariantToTracezillaSkuMapper,
        );
    }

    private function variant(?string $sku, string $id): ShopifyVariantData
    {
        return new ShopifyVariantData(
            graphQlId: "gid://shopify/ProductVariant/{$id}",
            legacyId: $id,
            sku: $sku,
            price: '10.00',
        );
    }
}
