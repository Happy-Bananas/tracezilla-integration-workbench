<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyTestControllerTest extends TestCase
{
    public function test_it_displays_the_shopify_configuration_without_revealing_credentials(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'secret-client-id',
            'services.shopify.client_secret' => 'secret-client-secret',
            'services.shopify.scope' => 'read_products',
            'services.shopify.api_version' => '2025-10',
        ]);

        Http::preventStrayRequests();

        $response = $this->get('/shopify');

        $response
            ->assertOk()
            ->assertViewIs('shopify.test')
            ->assertViewHas('config')
            ->assertSee('Shopify Connection Test')
            ->assertSee('test-shop.myshopify.com')
            ->assertSee('read_products')
            ->assertSee('Requested API Version')
            ->assertSee('2025-10')
            ->assertSee('✅ Configured')
            ->assertDontSee('secret-client-id')
            ->assertDontSee('secret-client-secret');

        Http::assertNothingSent();
    }

    public function test_it_reports_a_successful_shopify_connection(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'shop' => [
                        'name' => 'Test Shop',
                    ],
                ],
            ], 200, [
                'X-Shopify-API-Version' => '2025-10',
            ]),
        ]);

        $response = $this->post('/shopify/test');

        $response
            ->assertOk()
            ->assertViewIs('shopify.test')
            ->assertViewHas('result.message', 'Shopify connection and API version validated successfully.')
            ->assertViewHas('result.requested_api_version', '2025-10')
            ->assertViewHas('result.actual_api_version', '2025-10')
            ->assertViewHas('result.version_match', true)
            ->assertViewHas('error', null)
            ->assertSee('Success:')
            ->assertSee('Shopify connection and API version validated successfully.')
            ->assertDontSee('test-client-secret')
            ->assertDontSee('test-shopify-token');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://test-shop.myshopify.com/admin/oauth/access_token'
                && $request['grant_type'] === 'client_credentials'
                && $request['client_id'] === 'test-client-id'
                && $request['client_secret'] === 'test-client-secret'
                && $request['scope'] === 'read_products';
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json'
                && str_contains($request['query'], 'ConnectionStatus')
                && $request->hasHeader('X-Shopify-Access-Token', 'test-shopify-token');
        });
    }

    public function test_it_displays_a_safe_error_when_shopify_authentication_fails(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'errors' => 'Invalid API credentials',
            ], 401),
        ]);

        $response = $this->post('/shopify/test');

        $response
            ->assertOk()
            ->assertViewIs('shopify.test')
            ->assertViewHas('result', null)
            ->assertViewHas('error', function ($error): bool {
                return is_string($error)
                    && str_contains($error, '401');
            })
            ->assertSee('Error:')
            ->assertSee('401')
            ->assertDontSee('test-client-secret')
            ->assertDontSee('test-client-id');
    }

    public function test_it_reports_when_shopify_uses_a_different_api_version(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
            'services.shopify.api_version' => '2025-10',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'shop' => ['name' => 'Test Shop'],
                ],
            ], 200, [
                'X-Shopify-API-Version' => '2026-01',
            ]),
        ]);

        $response = $this->post('/shopify/test');

        $response
            ->assertOk()
            ->assertViewHas('result', null)
            ->assertViewHas('error', function ($error): bool {
                return $error === 'Shopify used API version [2026-01] instead of configured version [2025-10].';
            })
            ->assertSee('Error:')
            ->assertSee('2026-01')
            ->assertSee('2025-10')
            ->assertDontSee('test-client-secret')
            ->assertDontSee('test-shopify-token');
    }

    public function test_it_displays_products_returned_by_shopify(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'products' => [
                        'nodes' => [
                            ['id' => 'gid://shopify/Product/1', 'title' => 'Organic Bananas'],
                            ['id' => 'gid://shopify/Product/2', 'title' => 'Banana Chips'],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->post('/shopify/list-products');

        $response
            ->assertOk()
            ->assertViewIs('shopify.test')
            ->assertViewHas('products', function ($products): bool {
                return count($products) === 2
                    && $products[0]['title'] === 'Organic Bananas';
            })
            ->assertViewHas('error', null)
            ->assertSee('Products 2 returned')
            ->assertSee('Organic Bananas')
            ->assertSee('Banana Chips')
            ->assertDontSee('test-shopify-token');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json'
                && $request['variables'] === ['first' => 10]
                && $request->hasHeader('X-Shopify-Access-Token', 'test-shopify-token');
        });
    }

    public function test_it_displays_a_safe_error_when_shopify_product_listing_fails(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'errors' => 'Shopify is unavailable',
            ], 503),
        ]);

        $response = $this->post('/shopify/list-products');

        $response
            ->assertOk()
            ->assertViewIs('shopify.test')
            ->assertViewHas('products', null)
            ->assertViewHas('error', function ($error): bool {
                return is_string($error)
                    && str_contains($error, '503');
            })
            ->assertSee('Error:')
            ->assertSee('503')
            ->assertDontSee('test-client-secret')
            ->assertDontSee('test-shopify-token');
    }
}
