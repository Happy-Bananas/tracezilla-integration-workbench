<?php

namespace Tests\Unit\Features\ShopifyLocations;

use App\Features\ShopifyLocations\Data\ShopifyLocationData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ShopifyLocationDataTest extends TestCase
{
    public function test_it_creates_typed_location_data_from_a_shopify_response(): void
    {
        $location = ShopifyLocationData::fromApiResponse([
            'id' => 'gid://shopify/Location/123',
            'legacyResourceId' => '123',
            'name' => 'Development Warehouse',
            'isActive' => true,
            'hasActiveInventory' => true,
            'fulfillsOnlineOrders' => true,
            'address' => [
                'address1' => 'Banana Street 1',
                'address2' => null,
                'city' => 'Copenhagen',
                'province' => 'Capital Region',
                'country' => 'Denmark',
                'zip' => '1000',
            ],
        ]);

        $this->assertSame('gid://shopify/Location/123', $location->graphQlId);
        $this->assertSame('123', $location->legacyId);
        $this->assertSame('Development Warehouse', $location->name);
        $this->assertTrue($location->isActive);
        $this->assertTrue($location->hasActiveInventory);
        $this->assertTrue($location->fulfillsOnlineOrders);
        $this->assertSame(
            'Banana Street 1, 1000 Copenhagen, Capital Region, Denmark',
            $location->formattedAddress(),
        );
    }

    public function test_it_allows_a_location_without_an_address(): void
    {
        $location = ShopifyLocationData::fromApiResponse([
            'id' => 'gid://shopify/Location/123',
            'legacyResourceId' => '123',
            'name' => 'Virtual location',
            'isActive' => false,
            'hasActiveInventory' => false,
            'fulfillsOnlineOrders' => false,
            'address' => null,
        ]);

        $this->assertSame('', $location->formattedAddress());
    }

    public function test_it_rejects_a_location_without_a_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('field [name] cannot be empty');

        ShopifyLocationData::fromApiResponse([
            'id' => 'gid://shopify/Location/123',
            'legacyResourceId' => '123',
            'name' => '',
            'isActive' => true,
            'hasActiveInventory' => false,
            'fulfillsOnlineOrders' => false,
        ]);
    }
}
