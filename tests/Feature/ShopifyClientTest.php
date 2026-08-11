<?php

namespace Tests\Feature;

use App\Clients\Exceptions\ClientConfigurationException;
use App\Clients\Exceptions\ShopifyAuthenticationException;
use App\Clients\Exceptions\ShopifyGraphQlException;
use App\Clients\ShopifyClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyClientTest extends TestCase
{
    public function test_it_authenticates_and_uses_the_configured_api_version(): void
    {
        $this->configureShopify([
            'shop_url' => 'https://test-shop.myshopify.com/',
            'api_version' => '2026-01',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2026-01/graphql.json' => Http::response([
                'data' => ['shop' => ['name' => 'Test Shop']],
            ]),
        ]);

        $response = app(ShopifyClient::class)->graphql(
            'query TestShop { shop { name } }'
        );

        $this->assertSame('Test Shop', $response['data']['shop']['name']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://test-shop.myshopify.com/admin/api/2026-01/graphql.json'
                && $request->hasHeader('X-Shopify-Access-Token', 'test-shopify-token');
        });
    }

    public function test_it_rejects_missing_configuration_before_sending_a_request(): void
    {
        $this->configureShopify([
            'client_secret' => null,
        ]);

        Http::preventStrayRequests();

        $this->expectException(ClientConfigurationException::class);
        $this->expectExceptionMessage(
            'Missing required client configuration [services.shopify.client_secret].'
        );

        app(ShopifyClient::class);
    }

    public function test_it_rejects_an_authentication_response_without_an_access_token(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => Http::response(['scope' => 'read_products']),
        ]);

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage(
            'Shopify authentication response did not contain an access token.'
        );

        app(ShopifyClient::class);
    }

    public function test_it_throws_a_specific_exception_for_graphql_errors(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            $this->graphqlUrl() => Http::response([
                'data' => null,
                'errors' => [
                    ['message' => 'The query is invalid.'],
                ],
            ]),
        ]);

        try {
            app(ShopifyClient::class)->graphql('invalid query');
            $this->fail('Expected a ShopifyGraphQlException to be thrown.');
        } catch (ShopifyGraphQlException $exception) {
            $this->assertSame(
                'Shopify GraphQL request failed: The query is invalid.',
                $exception->getMessage()
            );
            $this->assertSame(
                [['message' => 'The query is invalid.']],
                $exception->errors()
            );
        }
    }

    public function test_it_rejects_an_invalid_graphql_json_response(): void
    {
        $this->configureShopify();

        Http::preventStrayRequests();

        Http::fake([
            $this->oauthUrl() => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            $this->graphqlUrl() => Http::response('not-json'),
        ]);

        $this->expectException(ShopifyGraphQlException::class);
        $this->expectExceptionMessage(
            'Shopify GraphQL returned an invalid JSON response.'
        );

        app(ShopifyClient::class)->graphql('query Test { shop { name } }');
    }

    private function configureShopify(array $overrides = []): void
    {
        config([
            'services.shopify' => array_merge([
                'shop_url' => 'test-shop.myshopify.com',
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'scope' => 'read_products',
                'api_version' => '2025-10',
                'timeout' => 30,
                'connect_timeout' => 10,
            ], $overrides),
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
}
