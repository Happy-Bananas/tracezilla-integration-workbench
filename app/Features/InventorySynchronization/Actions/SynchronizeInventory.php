<?php

namespace App\Features\InventorySynchronization\Actions;

use App\Features\InventorySynchronization\Mappers\TracezillaInventoryToShopifyQuantityMapper;
use App\Features\InventorySynchronization\Options\InventorySyncOptions;
use App\Features\InventorySynchronization\Results\InventorySyncItemResult;
use App\Features\InventorySynchronization\Results\InventorySyncResult;
use App\Features\InventorySynchronization\Results\InventorySyncStatus;
use App\Services\ShopifyInventoryService;
use App\Services\TracezillaInventoryService;
use Throwable;

final readonly class SynchronizeInventory
{
    /*
     * Example choices, not client configuration. Replace these two values
     * with the customer's intended location and warehouse relationship.
     */
    public const SHOPIFY_LOCATION_ID = 'gid://shopify/Location/115073778028';

    public const TRACEZILLA_WAREHOUSE_LOCATION_NUMBER = 2;

    public function __construct(
        private TracezillaInventoryService $tracezilla,
        private ShopifyInventoryService $shopify,
        private TracezillaInventoryToShopifyQuantityMapper $mapper,
    ) {}

    public function run(InventorySyncOptions $options): InventorySyncResult
    {
        $source = $this->tracezilla->getWarehouseInventory(
            self::TRACEZILLA_WAREHOUSE_LOCATION_NUMBER,
        );
        $source = $options->limit === null ? $source : array_slice($source, 0, $options->limit);
        $shopifyBySku = $this->shopify->getInventoryBySku(
            self::SHOPIFY_LOCATION_ID,
        );
        $result = new InventorySyncResult;

        foreach ($source as $inventory) {
            $shopify = $shopifyBySku[$inventory->sku] ?? null;

            if ($shopify === null) {
                $result->add(new InventorySyncItemResult(
                    $inventory->sku,
                    InventorySyncStatus::Skipped,
                    'No Shopify variant has this SKU.',
                ));

                continue;
            }

            if (! $shopify->tracked || $shopify->available === null) {
                $result->add(new InventorySyncItemResult(
                    $inventory->sku,
                    InventorySyncStatus::Skipped,
                    'Shopify does not track this item at the configured location.',
                ));

                continue;
            }

            try {
                $quantity = $this->mapper->map($inventory);

                if ($quantity === $shopify->available) {
                    $result->add(new InventorySyncItemResult(
                        $inventory->sku,
                        InventorySyncStatus::Unchanged,
                        "Quantity is already {$quantity}.",
                        $quantity,
                        $quantity,
                    ));
                } elseif ($options->dryRun) {
                    $result->add(new InventorySyncItemResult(
                        $inventory->sku,
                        InventorySyncStatus::WouldUpdate,
                        "Would change quantity from {$shopify->available} to {$quantity}.",
                        $shopify->available,
                        $quantity,
                    ));
                } else {
                    $this->shopify->setAvailable(
                        $shopify,
                        $quantity,
                        self::SHOPIFY_LOCATION_ID,
                    );
                    $result->add(new InventorySyncItemResult(
                        $inventory->sku,
                        InventorySyncStatus::Updated,
                        "Changed quantity from {$shopify->available} to {$quantity}.",
                        $shopify->available,
                        $quantity,
                    ));
                }
            } catch (Throwable) {
                $result->add(new InventorySyncItemResult(
                    $inventory->sku,
                    InventorySyncStatus::Failed,
                    'The quantity could not be mapped or written safely.',
                ));
            }
        }

        return $result;
    }
}
