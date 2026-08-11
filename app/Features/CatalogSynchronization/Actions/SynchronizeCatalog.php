<?php

namespace App\Features\CatalogSynchronization\Actions;

use App\Features\CatalogSynchronization\Mappers\ShopifyVariantToTracezillaSkuMapper;
use App\Features\CatalogSynchronization\Options\CatalogSyncOptions;
use App\Features\CatalogSynchronization\Results\CatalogSyncItemResult;
use App\Features\CatalogSynchronization\Results\CatalogSyncReason;
use App\Features\CatalogSynchronization\Results\CatalogSyncResult;
use App\Features\CatalogSynchronization\Results\CatalogSyncStatus;
use App\Services\ShopifyCatalogService;
use App\Services\TracezillaSkuService;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use Throwable;

final readonly class SynchronizeCatalog
{
    public function __construct(
        private ShopifyCatalogService $shopifyCatalog,
        private TracezillaSkuService $tracezillaSkus,
        private ShopifyVariantToTracezillaSkuMapper $skuMapper,
    ) {}

    public function run(CatalogSyncOptions $options): CatalogSyncResult
    {
        $variants = $this->shopifyCatalog->getProductVariants();
        $existingSkuCodes = $this->tracezillaSkus->getSkuCodes();
        $selectedVariants = $options->limit === null
            ? $variants
            : array_slice($variants, 0, $options->limit);

        $result = new CatalogSyncResult(
            sourceCount: count($variants),
            existingSkuCount: count($existingSkuCodes),
            selectedCount: count($selectedVariants),
            dryRun: $options->dryRun,
            limit: $options->limit,
        );

        $existingSkuSet = array_fill_keys(array_map(
            static fn (string $sku): string => trim($sku),
            $existingSkuCodes,
        ), true);
        $seenShopifySkuSet = [];

        foreach ($selectedVariants as $variant) {
            if (! $variant->hasSku()) {
                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $variant->sku,
                    status: CatalogSyncStatus::Invalid,
                    reason: CatalogSyncReason::MissingSku,
                    message: 'Shopify variant does not have an SKU.',
                ));

                continue;
            }

            $sku = trim($variant->sku);

            if (isset($existingSkuSet[$sku])) {
                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::Skipped,
                    reason: CatalogSyncReason::AlreadyExists,
                    message: 'SKU already exists in tracezilla.',
                ));

                continue;
            }

            if (isset($seenShopifySkuSet[$sku])) {
                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::Skipped,
                    reason: CatalogSyncReason::DuplicateShopifySku,
                    message: 'Another Shopify variant in this run has the same SKU.',
                ));

                continue;
            }

            $seenShopifySkuSet[$sku] = true;

            try {
                $skuData = $this->skuMapper->map($variant);
            } catch (InvalidArgumentException $exception) {
                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::Invalid,
                    reason: CatalogSyncReason::InvalidPayload,
                    message: $exception->getMessage(),
                ));

                continue;
            }

            if ($options->dryRun) {
                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::WouldCreate,
                    reason: CatalogSyncReason::DryRun,
                    message: 'SKU would be created during execution.',
                ));

                continue;
            }

            try {
                $response = $this->tracezillaSkus->createSku($skuData);
                $existingSkuSet[$sku] = true;

                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::Created,
                    reason: CatalogSyncReason::Created,
                    message: 'SKU was created in tracezilla.',
                    details: ['response' => $response],
                ));
            } catch (RequestException $exception) {
                $status = $exception->response->status();

                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::Failed,
                    reason: CatalogSyncReason::TracezillaError,
                    message: "tracezilla rejected the SKU request with HTTP {$status}.",
                    details: ['status' => $status],
                ));
            } catch (Throwable $exception) {
                $result->add(new CatalogSyncItemResult(
                    sourceId: $variant->graphQlId,
                    sku: $sku,
                    status: CatalogSyncStatus::Failed,
                    reason: CatalogSyncReason::TracezillaError,
                    message: 'An unexpected error occurred while creating the tracezilla SKU.',
                    details: ['exception' => $exception::class],
                ));
            }
        }

        return $result;
    }
}
