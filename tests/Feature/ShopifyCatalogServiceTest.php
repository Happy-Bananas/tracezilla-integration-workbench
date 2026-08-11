<?php

namespace Tests\Feature;

use App\Features\CatalogSynchronization\Data\ShopifyVariantData;
use App\Services\ShopifyCatalogService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyCatalogServiceTest extends TestCase
{
    public function test_it_returns_product_variants_from_shopify(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response($this->variantsResponse([
                $this->variant('BANANA-001', '1', '10.00'),
                $this->variant('BANANA-002', '2', '12.00'),
            ])),
        ]);

        $variants = app(ShopifyCatalogService::class)->getProductVariants();

        $this->assertEquals([
            $this->variantData('BANANA-001', '1', '10.00'),
            $this->variantData('BANANA-002', '2', '12.00'),
        ], $variants);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === $this->graphqlUrl()
                && $request['variables'] === [
                    'first' => 250,
                    'after' => null,
                ]
                && $request->hasHeader('X-Shopify-Access-Token', 'test-shopify-token');
        });
    }

    public function test_it_combines_multiple_pages_of_product_variants(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::sequence()
                ->push($this->variantsResponse([
                    $this->variant('BANANA-001', '1', '10.00'),
                ], true, 'page-one-cursor'))
                ->push($this->variantsResponse([
                    $this->variant('BANANA-002', '2', '12.00'),
                ])),
        ]);

        $variants = app(ShopifyCatalogService::class)->getProductVariants();

        $this->assertEquals([
            $this->variantData('BANANA-001', '1', '10.00'),
            $this->variantData('BANANA-002', '2', '12.00'),
        ], $variants);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === $this->graphqlUrl()
                && $request['variables']['after'] === 'page-one-cursor';
        });
    }

    public function test_it_maps_variants_by_sku_and_ignores_empty_skus(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response($this->variantsResponse([
                $this->variant('BANANA-001', '1', '10.00'),
                $this->variant('', '2', '12.00'),
            ])),
        ]);

        $mapping = app(ShopifyCatalogService::class)->getVariantSkuMapping();

        $this->assertSame([
            'BANANA-001' => [
                'variant_id' => '1',
                'price' => '10.00',
            ],
        ], $mapping);
    }

    public function test_the_last_variant_wins_when_shopify_returns_duplicate_skus(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response($this->variantsResponse([
                $this->variant('BANANA-001', '1', '10.00'),
                $this->variant('BANANA-001', '2', '12.00'),
            ])),
        ]);

        $mapping = app(ShopifyCatalogService::class)->getVariantSkuMapping();

        $this->assertSame([
            'BANANA-001' => [
                'variant_id' => '2',
                'price' => '12.00',
            ],
        ], $mapping);
    }

    public function test_it_throws_an_exception_when_shopify_rejects_the_graphql_request(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response([
                'errors' => 'Internal server error',
            ], 500),
        ]);

        $this->expectException(RequestException::class);

        app(ShopifyCatalogService::class)->getProductVariants();
    }

    private function configureShopify(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
        ]);
    }

    private function oauthUrl(): string
    {
        return 'https://test-shop.myshopify.com/admin/oauth/access_token';
    }

    private function graphqlUrl(): string
    {
        return 'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json';
    }

    private function oauthResponse()
    {
        return Http::response([
            'access_token' => 'test-shopify-token',
        ]);
    }

    private function variant(string $sku, string $id, string $price): array
    {
        return [
            'id' => "gid://shopify/ProductVariant/{$id}",
            'legacyResourceId' => $id,
            'sku' => $sku,
            'price' => $price,
        ];
    }

    private function variantData(string $sku, string $id, string $price): ShopifyVariantData
    {
        return new ShopifyVariantData(
            graphQlId: "gid://shopify/ProductVariant/{$id}",
            legacyId: $id,
            sku: $sku,
            price: $price,
        );
    }

    private function variantsResponse(
        array $variants,
        bool $hasNextPage = false,
        ?string $endCursor = null,
    ): array {
        return [
            'data' => [
                'productVariants' => [
                    'nodes' => $variants,
                    'pageInfo' => [
                        'hasNextPage' => $hasNextPage,
                        'endCursor' => $endCursor,
                    ],
                ],
            ],
        ];
    }
}
