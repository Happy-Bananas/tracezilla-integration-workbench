<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaOrdersFromShopifyCommandTest extends TestCase
{
    public function test_dry_run_does_not_write_a_sales_order(): void
    {
        $this->fakeApis();

        $this->artisan('tracezilla:orders-from-shopify', ['--limit' => 1])
            ->expectsOutputToContain('DRY RUN: no tracezilla sales orders were created.')
            ->expectsOutputToContain('Created: 0, would create: 1, skipped: 0, failed: 0')
            ->assertSuccessful();

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://tracezilla.test/api/v1/team/orders/sales');
    }

    public function test_execute_writes_the_mapped_sales_order(): void
    {
        $this->fakeApis();

        $this->artisan('tracezilla:orders-from-shopify', [
            '--execute' => true,
            '--limit' => 1,
        ])
            ->expectsOutputToContain('Created: 1, would create: 0, skipped: 0, failed: 0')
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'PUT'
                || $request->url() !== 'https://tracezilla.test/api/v1/team/orders/sales') {
                return false;
            }

            return $request['order_header']['ext_ref'] === 'SHP123'
                && $request['order_header']['partners']['customer']['partner_id'] === 'customer-id'
                && $request['order_header']['partners']['pickup_from']['location_id'] === 'warehouse-location-id'
                && $request['outbound_skus'][0] === [
                    'sku_code' => 'BAN-001',
                    'quantity' => 2,
                    'unit_price' => 10.0,
                    'price_per' => 'stock_unit',
                ]
                && $request['post_save_action'] === 'none';
        });
    }

    private function fakeApis(): void
    {
        config([
            'services.shopify.shop_url' => 'shop.myshopify.com',
            'services.shopify.client_id' => 'client-id',
            'services.shopify.client_secret' => 'client-secret',
            'services.shopify.scope' => 'read_orders',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'team',
            'services.tracezilla.api_key' => 'api-key',
        ]);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            return match (true) {
                $request->url() === 'https://shop.myshopify.com/admin/oauth/access_token' => Http::response([
                    'access_token' => 'shopify-token',
                ]),
                $request->url() === 'https://shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(
                    $this->shopifyResponse()
                ),
                str_starts_with($request->url(), 'https://tracezilla.test/api/v1/team/partners?') => Http::response([
                    'data' => [[
                        'id' => 'customer-id',
                        'name' => 'Banana primary webshop',
                        'owned_by_id' => 7,
                        'locations' => [[
                            'id' => 'customer-location-id',
                            'is_primary' => true,
                        ]],
                    ]],
                ]),
                $request->url() === 'https://tracezilla.test/api/v1/team/location-by-number/2' => Http::response([
                    'data' => [
                        'id' => 'warehouse-location-id',
                        'partner_id' => 'warehouse-partner-id',
                    ],
                ]),
                str_starts_with($request->url(), 'https://tracezilla.test/api/v1/team/orders/sales?') => Http::response([
                    'data' => [],
                ]),
                $request->method() === 'PUT'
                    && $request->url() === 'https://tracezilla.test/api/v1/team/orders/sales' => Http::response([
                        'data' => ['id' => 'sales-order-id'],
                    ]),
                default => Http::response(['message' => 'Unexpected fake request.'], 500),
            };
        });
    }

    private function shopifyResponse(): array
    {
        return [
            'data' => [
                'orders' => [
                    'nodes' => [[
                        'id' => 'gid://shopify/Order/123',
                        'legacyResourceId' => '123',
                        'name' => '#1001',
                        'createdAt' => '2026-07-30T09:00:00Z',
                        'cancelledAt' => null,
                        'email' => 'jane@example.com',
                        'phone' => '+4512345678',
                        'note' => null,
                        'poNumber' => null,
                        'currencyCode' => 'DKK',
                        'shippingAddress' => [
                            'name' => 'Jane Doe',
                            'company' => null,
                            'address1' => 'Main Street 1',
                            'address2' => null,
                            'zip' => '1000',
                            'city' => 'Copenhagen',
                            'province' => null,
                            'provinceCode' => null,
                            'countryCodeV2' => 'DK',
                            'phone' => '+4512345678',
                        ],
                        'lineItems' => [
                            'nodes' => [[
                                'sku' => 'BAN-001',
                                'currentQuantity' => 2,
                                'discountedUnitPriceAfterAllDiscountsSet' => [
                                    'shopMoney' => [
                                        'amount' => '10.00',
                                        'currencyCode' => 'DKK',
                                    ],
                                ],
                            ]],
                            'pageInfo' => ['hasNextPage' => false],
                        ],
                    ]],
                    'pageInfo' => [
                        'hasNextPage' => false,
                        'endCursor' => null,
                    ],
                ],
            ],
        ];
    }
}
