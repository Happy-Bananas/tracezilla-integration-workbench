<?php

namespace Tests\Unit\Features\CatalogSynchronization;

use App\Features\CatalogSynchronization\Data\ShopifyVariantData;
use App\Features\CatalogSynchronization\Data\TracezillaSkuData;
use App\Features\CatalogSynchronization\Mappers\ShopifyVariantToTracezillaSkuMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ShopifyVariantToTracezillaSkuMapperTest extends TestCase
{
    public function test_it_maps_a_shopify_variant_to_a_tracezilla_sku(): void
    {
        $sku = (new ShopifyVariantToTracezillaSkuMapper)->map(new ShopifyVariantData(
            graphQlId: 'gid://shopify/ProductVariant/123',
            legacyId: '123',
            sku: ' BANANA-001 ',
            price: '10.00',
        ));

        $this->assertEquals(new TracezillaSkuData(
            skuCode: 'BANANA-001',
            globalName: 'BANANA-001',
            weightFactorNet: 1.0,
            weightFactorGross: 1.0,
            unitOfMeasure: 'pcs',
            lotUnit: 'colli',
            defaultUomConversion: 1.0,
        ), $sku);
    }

    public function test_it_rejects_a_shopify_variant_without_an_sku(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Shopify variant [gid://shopify/ProductVariant/123] cannot be mapped without an SKU.'
        );

        (new ShopifyVariantToTracezillaSkuMapper)->map(new ShopifyVariantData(
            graphQlId: 'gid://shopify/ProductVariant/123',
            legacyId: '123',
            sku: null,
            price: '10.00',
        ));
    }
}
