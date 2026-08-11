<?php

namespace Tests\Feature\Console;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PullCatalogFromShopifyCommandTest extends TestCase
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

    public function test_it_reports_catalog_differences_without_writes(): void
    {
        $this->fakeCatalogs();

        $this->artisan('pull-catalog-from-shopify')
            ->expectsOutputToContain('Only in Shopify: SHOPIFY')
            ->expectsOutputToContain('Only in tracezilla: TRACEZILLA')
            ->assertSuccessful();

        $this->assertNoApiWriteWasSent();
    }

    private function fakeCatalogs(): void
    {
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response(['access_token' => 'token']),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(['data' => ['productVariants' => [
                'nodes' => [
                    ['id' => 'gid://shopify/ProductVariant/1', 'legacyResourceId' => '1', 'sku' => ' BOTH ', 'price' => '10.00'],
                    ['id' => 'gid://shopify/ProductVariant/2', 'legacyResourceId' => '2', 'sku' => 'SHOPIFY', 'price' => '11.00'],
                ],
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            ]]]),
            'https://tracezilla.test/api/v1/team/skus*' => Http::response(['data' => [
                ['sku_code' => 'BOTH'], ['sku_code' => 'TRACEZILLA'],
            ]]),
        ]);
    }

    private function assertNoApiWriteWasSent(): void
    {
        Http::assertNotSent(fn (Request $request): bool => in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)
            || ($request->method() === 'POST' && ! str_contains($request->url(), 'access_token') && ! str_contains($request->url(), 'graphql.json')));
    }
}
