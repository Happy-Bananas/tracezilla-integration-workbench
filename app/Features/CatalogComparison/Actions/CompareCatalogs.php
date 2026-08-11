<?php

namespace App\Features\CatalogComparison\Actions;

use App\Features\CatalogComparison\Options\CatalogComparisonOptions;
use App\Features\CatalogComparison\Results\CatalogComparisonResult;
use App\Services\ShopifyCatalogService;
use App\Services\TracezillaSkuService;

final readonly class CompareCatalogs
{
    public function __construct(
        private ShopifyCatalogService $shopify,
        private TracezillaSkuService $tracezilla,
    ) {}

    public function run(CatalogComparisonOptions $options): CatalogComparisonResult
    {
        $variants = $this->shopify->getProductVariants();
        $tracezillaSkus = $this->tracezilla->getSkuCodes();
        $shopifyCount = count($variants);
        $tracezillaCount = count($tracezillaSkus);

        if ($options->limit !== null) {
            $variants = array_slice($variants, 0, $options->limit);
            $tracezillaSkus = array_slice($tracezillaSkus, 0, $options->limit);
        }

        $blankIds = [];
        $shopifySkus = [];
        foreach ($variants as $variant) {
            if (! $variant->hasSku()) {
                $blankIds[] = $variant->graphQlId;

                continue;
            }
            $shopifySkus[] = trim($variant->sku);
        }

        $tracezillaSkus = array_values(array_filter(
            array_map(static fn (string $sku): string => trim($sku), $tracezillaSkus),
            static fn (string $sku): bool => $sku !== '',
        ));
        $shopifySkus = array_values(array_unique($shopifySkus));
        $tracezillaSkus = array_values(array_unique($tracezillaSkus));
        sort($shopifySkus);
        sort($tracezillaSkus);

        return new CatalogComparisonResult(
            presentInBoth: array_values(array_intersect($shopifySkus, $tracezillaSkus)),
            onlyInShopify: array_values(array_diff($shopifySkus, $tracezillaSkus)),
            onlyInTracezilla: array_values(array_diff($tracezillaSkus, $shopifySkus)),
            blankShopifyVariantIds: $blankIds,
            shopifyCount: $shopifyCount,
            tracezillaCount: $tracezillaCount,
            limit: $options->limit,
        );
    }
}
