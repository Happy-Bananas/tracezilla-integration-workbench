<?php

namespace Tests\Unit\Features\CatalogSynchronization;

use App\Features\CatalogSynchronization\Results\CatalogSyncItemResult;
use App\Features\CatalogSynchronization\Results\CatalogSyncReason;
use App\Features\CatalogSynchronization\Results\CatalogSyncResult;
use App\Features\CatalogSynchronization\Results\CatalogSyncStatus;
use PHPUnit\Framework\TestCase;

class CatalogSyncResultTest extends TestCase
{
    public function test_it_produces_stable_counts_and_serialized_reason_codes(): void
    {
        $result = new CatalogSyncResult(
            sourceCount: 3,
            existingSkuCount: 1,
            selectedCount: 3,
            dryRun: true,
            limit: null,
        );

        $result->add(new CatalogSyncItemResult(
            sourceId: 'variant-1',
            sku: 'BANANA-001',
            status: CatalogSyncStatus::Skipped,
            reason: CatalogSyncReason::AlreadyExists,
        ));
        $result->add(new CatalogSyncItemResult(
            sourceId: 'variant-2',
            sku: 'BANANA-002',
            status: CatalogSyncStatus::WouldCreate,
            reason: CatalogSyncReason::DryRun,
        ));
        $result->add(new CatalogSyncItemResult(
            sourceId: 'variant-3',
            sku: null,
            status: CatalogSyncStatus::Invalid,
            reason: CatalogSyncReason::MissingSku,
            message: 'A Shopify SKU is required.',
        ));

        $this->assertSame([
            'source_count' => 3,
            'existing_sku_count' => 1,
            'selected_count' => 3,
            'processed_count' => 3,
            'created_count' => 0,
            'would_create_count' => 1,
            'skipped_count' => 1,
            'invalid_count' => 1,
            'failed_count' => 0,
            'dry_run' => true,
            'limit' => null,
        ], $result->summary());
        $this->assertFalse($result->hasFailures());
        $this->assertSame('already_exists', $result->toArray()['items'][0]['reason']);
        $this->assertSame('would_create', $result->toArray()['items'][1]['status']);
    }

    public function test_it_reports_operational_failures(): void
    {
        $result = new CatalogSyncResult(
            sourceCount: 1,
            existingSkuCount: 0,
            selectedCount: 1,
            dryRun: false,
            limit: 1,
        );

        $result->add(new CatalogSyncItemResult(
            sourceId: 'variant-1',
            sku: 'BANANA-001',
            status: CatalogSyncStatus::Failed,
            reason: CatalogSyncReason::TracezillaError,
            message: 'tracezilla rejected the request with HTTP 422.',
            details: ['status' => 422],
        ));

        $this->assertTrue($result->hasFailures());
        $this->assertSame(1, $result->count(CatalogSyncStatus::Failed));
        $this->assertSame(422, $result->toArray()['items'][0]['details']['status']);
    }
}
