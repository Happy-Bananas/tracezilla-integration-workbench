<?php

namespace Tests\Feature\Console;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushCatalogToShopifyCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'client',
            'services.shopify.client_secret' => 'secret',
            'services.shopify.scope' => 'read_products',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'team',
            'services.tracezilla.api_key' => 'key',
        ]);
        Http::preventStrayRequests();
    }

    public function test_it_reports_missing_shopify_products_without_writes(): void
    {
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response(['access_token' => 'token']),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(['data' => ['productVariants' => [
                'nodes' => [
                    ['id' => 'gid://shopify/ProductVariant/1', 'legacyResourceId' => '1', 'sku' => 'BOTH', 'price' => '10.00'],
                ],
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            ]]]),
            'https://tracezilla.test/api/v1/team/skus*' => Http::response(['data' => [
                ['sku_code' => 'BOTH'], ['sku_code' => 'TRACEZILLA'],
            ]]),
        ]);

        $this->artisan('push-catalog-to-shopify')
            ->expectsOutputToContain('READ ONLY: product creation and price updates are intentionally disabled.')
            ->expectsOutputToContain('Would require a Shopify product decision: TRACEZILLA')
            ->assertSuccessful();

        Http::assertNotSent(fn (Request $request): bool => in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)
            || ($request->method() === 'POST' && ! str_contains($request->url(), 'access_token') && ! str_contains($request->url(), 'graphql.json')));
    }
}
