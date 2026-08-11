<?php

namespace Tests\Unit\Features\CatalogSynchronization;

use App\Features\CatalogSynchronization\Data\ShopifyVariantData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ShopifyVariantDataTest extends TestCase
{
    public function test_it_creates_a_typed_variant_from_a_shopify_api_response(): void
    {
        $variant = ShopifyVariantData::fromApiResponse([
            'id' => 'gid://shopify/ProductVariant/123',
            'legacyResourceId' => '123',
            'sku' => ' BANANA-001 ',
            'price' => '10.00',
        ]);

        $this->assertSame('gid://shopify/ProductVariant/123', $variant->graphQlId);
        $this->assertSame('123', $variant->legacyId);
        $this->assertSame('BANANA-001', $variant->sku);
        $this->assertSame('10.00', $variant->price);
        $this->assertTrue($variant->hasSku());
    }

    public function test_it_allows_a_variant_without_an_sku(): void
    {
        $variant = ShopifyVariantData::fromApiResponse([
            'id' => 'gid://shopify/ProductVariant/123',
            'legacyResourceId' => 123,
            'sku' => null,
            'price' => '10.00',
        ]);

        $this->assertSame('123', $variant->legacyId);
        $this->assertNull($variant->sku);
        $this->assertFalse($variant->hasSku());
    }

    public function test_it_rejects_an_api_response_missing_a_required_field(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Shopify variant field [price] must be a scalar value.');

        ShopifyVariantData::fromApiResponse([
            'id' => 'gid://shopify/ProductVariant/123',
            'legacyResourceId' => '123',
            'sku' => 'BANANA-001',
        ]);
    }
}
