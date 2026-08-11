<?php

namespace Tests\Feature;

use App\Clients\ShopifyClient;
use App\Features\InventorySynchronization\Data\ShopifyInventoryItemData;
use App\Services\ShopifyInventoryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ShopifyInventoryServiceTest extends TestCase
{
    public function test_it_sets_an_absolute_quantity_with_compare_and_set(): void
    {
        $this->configureShopify();
        Http::preventStrayRequests();
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'inventorySetQuantities' => [
                        'inventoryAdjustmentGroup' => ['changes' => []],
                        'userErrors' => [],
                    ],
                ],
            ]),
        ]);

        $service = new ShopifyInventoryService(new ShopifyClient);
        $service->setAvailable(
            new ShopifyInventoryItemData('variant-1', 'item-1', 'BANANA-001', true, 3),
            8,
            'gid://shopify/Location/1',
        );

        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), '/graphql.json')) {
                return false;
            }

            $input = $request['variables']['input'] ?? [];

            return str_contains((string) $request['query'], 'inventorySetQuantities')
                && $input['name'] === 'available'
                && $input['quantities'][0] === [
                    'inventoryItemId' => 'item-1',
                    'locationId' => 'gid://shopify/Location/1',
                    'quantity' => 8,
                    'compareQuantity' => 3,
                ];
        });
    }

    public function test_it_turns_shopify_user_errors_into_an_exception(): void
    {
        $this->configureShopify();
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'inventorySetQuantities' => [
                        'userErrors' => [['message' => 'Quantity changed elsewhere.']],
                    ],
                ],
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Quantity changed elsewhere.');

        (new ShopifyInventoryService(new ShopifyClient))->setAvailable(
            new ShopifyInventoryItemData('variant-1', 'item-1', 'BANANA-001', true, 3),
            8,
            'gid://shopify/Location/1',
        );
    }

    private function configureShopify(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'client',
            'services.shopify.client_secret' => 'secret',
            'services.shopify.scope' => 'read_inventory,write_inventory',
        ]);
    }
}
