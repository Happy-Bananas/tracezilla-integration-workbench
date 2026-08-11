<?php

namespace App\Features\CatalogSynchronization\Mappers;

use App\Features\CatalogSynchronization\Data\ShopifyVariantData;
use App\Features\CatalogSynchronization\Data\TracezillaSkuData;
use InvalidArgumentException;

final readonly class ShopifyVariantToTracezillaSkuMapper
{
    public function map(ShopifyVariantData $variant): TracezillaSkuData
    {
        if (! $variant->hasSku()) {
            throw new InvalidArgumentException(
                "Shopify variant [{$variant->graphQlId}] cannot be mapped without an SKU."
            );
        }

        return new TracezillaSkuData(
            skuCode: trim($variant->sku),
            globalName: trim($variant->sku),

            // Demonstration mapping only. Replace these values with rules that
            // match the customer's products, units, weights, and conversions.
            weightFactorNet: 1.0,
            weightFactorGross: 1.0,
            unitOfMeasure: 'pcs',
            lotUnit: 'colli',
            defaultUomConversion: 1.0,
        );
    }
}
