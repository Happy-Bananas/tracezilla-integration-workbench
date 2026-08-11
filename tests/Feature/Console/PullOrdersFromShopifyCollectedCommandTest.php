<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PullOrdersFromShopifyCollectedCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'client',
            'services.shopify.client_secret' => 'secret',
            'services.shopify.scope' => 'read_orders',
        ]);
        Http::preventStrayRequests();
    }

    public function test_it_groups_orders_by_business_date_sku_and_currency(): void
    {
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response(['access_token' => 'token']),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(['data' => ['orders' => [
                'nodes' => [[
                    'id' => 'gid://shopify/Order/1', 'legacyResourceId' => '1', 'name' => '#1',
                    'createdAt' => '2026-08-03T23:30:00Z', 'cancelledAt' => null, 'currencyCode' => 'DKK',
                    'lineItems' => ['nodes' => [[
                        'sku' => 'BAN-001', 'currentQuantity' => 2,
                        'discountedUnitPriceAfterAllDiscountsSet' => ['shopMoney' => ['amount' => '12.50', 'currencyCode' => 'DKK']],
                    ]], 'pageInfo' => ['hasNextPage' => false]],
                ]],
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            ]]]),
        ]);

        $this->assertSame(0, Artisan::call('pull-orders-from-shopify-collected', [
            '--timezone' => 'Europe/Copenhagen',
            '--json' => true,
        ]));
        $output = Artisan::output();
        $this->assertStringContainsString('"date": "2026-08-04"', $output);
        $this->assertStringContainsString('"quantity": 2', $output);
    }
}
