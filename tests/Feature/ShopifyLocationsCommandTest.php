<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyLocationsCommandTest extends TestCase
{
    public function test_it_lists_shopify_locations_in_a_table(): void
    {
        $this->configureShopify();
        Http::preventStrayRequests();
        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response($this->locationsResponse([
                $this->location('1', 'Development Warehouse', true),
                $this->location('2', 'Closed Store', false),
            ])),
        ]);

        $exitCode = Artisan::call('shopify:locations');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('2 location(s) returned.', $output);
        $this->assertStringContainsString('Development Warehouse', $output);
        $this->assertStringContainsString('Active', $output);
        $this->assertStringContainsString('Closed Store', $output);
        $this->assertStringContainsString('Inactive', $output);
    }

    public function test_it_retrieves_every_page_of_locations(): void
    {
        $this->configureShopify();
        Http::preventStrayRequests();
        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::sequence()
                ->push($this->locationsResponse([
                    $this->location('1', 'Warehouse', true),
                ], true, 'next-location-page'))
                ->push($this->locationsResponse([
                    $this->location('2', 'Store', true),
                ])),
        ]);

        $this->artisan('shopify:locations')
            ->expectsOutputToContain('2 location(s) returned.')
            ->expectsOutputToContain('Warehouse')
            ->expectsOutputToContain('Store')
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            return $request->url() === $this->graphqlUrl()
                && $request['variables'] === [
                    'first' => 250,
                    'after' => 'next-location-page',
                ];
        });
    }

    public function test_it_explains_when_no_locations_are_available(): void
    {
        $this->configureShopify();
        Http::preventStrayRequests();
        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response($this->locationsResponse([])),
        ]);

        $this->artisan('shopify:locations')
            ->expectsOutputToContain('0 location(s) returned.')
            ->expectsOutputToContain('No Shopify locations are available to this app.')
            ->assertSuccessful();
    }

    public function test_it_can_return_structured_json(): void
    {
        $this->configureShopify();
        Http::preventStrayRequests();
        Http::fake([
            $this->oauthUrl() => $this->oauthResponse(),
            $this->graphqlUrl() => Http::response($this->locationsResponse([
                $this->location('1', 'Development Warehouse', true),
            ])),
        ]);

        $exitCode = Artisan::call('shopify:locations', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"count": 1', $output);
        $this->assertStringContainsString(
            '"graph_ql_id": "gid://shopify/Location/1"',
            $output,
        );
        $this->assertStringContainsString('"name": "Development Warehouse"', $output);
    }

    private function configureShopify(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_locations',
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

    private function location(string $id, string $name, bool $isActive): array
    {
        return [
            'id' => "gid://shopify/Location/{$id}",
            'legacyResourceId' => $id,
            'name' => $name,
            'isActive' => $isActive,
            'hasActiveInventory' => $isActive,
            'fulfillsOnlineOrders' => $isActive,
            'address' => [
                'address1' => 'Banana Street 1',
                'address2' => null,
                'city' => 'Copenhagen',
                'province' => null,
                'country' => 'Denmark',
                'zip' => '1000',
            ],
        ];
    }

    private function locationsResponse(
        array $locations,
        bool $hasNextPage = false,
        ?string $endCursor = null,
    ): array {
        return [
            'data' => [
                'locations' => [
                    'nodes' => $locations,
                    'pageInfo' => [
                        'hasNextPage' => $hasNextPage,
                        'endCursor' => $endCursor,
                    ],
                ],
            ],
        ];
    }
}
