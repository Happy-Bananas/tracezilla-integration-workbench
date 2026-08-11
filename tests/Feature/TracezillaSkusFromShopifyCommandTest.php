<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaSkusFromShopifyCommandTest extends TestCase
{
    public function test_it_creates_a_tracezilla_sku_for_a_shopify_variant(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'test-team',
            'services.tracezilla.api_key' => 'test-api-key',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'productVariants' => [
                        'nodes' => [[
                            'id' => 'gid://shopify/ProductVariant/1',
                            'legacyResourceId' => '1',
                            'sku' => 'BANANA-001',
                            'price' => '10.00',
                        ]],
                        'pageInfo' => [
                            'hasNextPage' => false,
                            'endCursor' => null,
                        ],
                    ],
                ],
            ]),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::sequence()
                ->push(['data' => []])
                ->push(['data' => ['sku_code' => 'BANANA-001']]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--execute' => true])
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus'
                && $request->data() === [
                    'sku_code' => 'BANANA-001',
                    'global_name' => 'BANANA-001',
                    'weight_factor_net' => 1.0,
                    'weight_factor_gross' => 1.0,
                    'unit_of_measure' => 'pcs',
                    'lot_unit' => 'colli',
                    'default_uom_conversion' => 1.0,
                ];
        });
    }

    public function test_it_skips_a_shopify_sku_that_already_exists_in_tracezilla(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'test-team',
            'services.tracezilla.api_key' => 'test-api-key',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'productVariants' => [
                        'nodes' => [[
                            'id' => 'gid://shopify/ProductVariant/1',
                            'legacyResourceId' => '1',
                            'sku' => 'BANANA-001',
                            'price' => '10.00',
                        ]],
                        'pageInfo' => [
                            'hasNextPage' => false,
                            'endCursor' => null,
                        ],
                    ],
                ],
            ]),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [[
                    'sku_code' => 'BANANA-001',
                ]],
            ]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify')
            ->expectsOutputToContain('Created: 0, would create: 0, skipped: 1, invalid: 0, failed: 0')
            ->assertSuccessful();

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus';
        });
    }

    public function test_it_ignores_a_shopify_variant_without_an_sku(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'productVariants' => [
                        'nodes' => [[
                            'id' => 'gid://shopify/ProductVariant/1',
                            'legacyResourceId' => '1',
                            'sku' => '',
                            'price' => '10.00',
                        ]],
                        'pageInfo' => [
                            'hasNextPage' => false,
                            'endCursor' => null,
                        ],
                    ],
                ],
            ]),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [],
            ]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify')
            ->expectsOutputToContain('Created: 0, would create: 0, skipped: 0, invalid: 1, failed: 0')
            ->assertSuccessful();

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus';
        });
    }

    public function test_it_retrieves_all_pages_of_shopify_variants(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::sequence()
                ->push($this->shopifyVariantsResponse([
                    $this->variant('BANANA-001', '1'),
                ], true, 'page-one-cursor'))
                ->push($this->shopifyVariantsResponse([
                    $this->variant('BANANA-002', '2'),
                ])),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::sequence()
                ->push(['data' => []])
                ->push(['data' => ['sku_code' => 'BANANA-001']])
                ->push(['data' => ['sku_code' => 'BANANA-002']]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--execute' => true])
            ->expectsOutputToContain('Shopify variants: 2 returned, 2 selected, 2 processed.')
            ->expectsOutputToContain('Created: 2, would create: 0, skipped: 0, invalid: 0, failed: 0')
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json'
                && $request['variables']['after'] === 'page-one-cursor';
        });

        foreach (['BANANA-001', 'BANANA-002'] as $sku) {
            Http::assertSent(function (Request $request) use ($sku): bool {
                return $request->method() === 'POST'
                    && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus'
                    && $request['sku_code'] === $sku;
            });
        }
    }

    public function test_it_handles_new_existing_and_missing_skus_together(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(
                $this->shopifyVariantsResponse([
                    $this->variant('BANANA-NEW', '1'),
                    $this->variant('BANANA-EXISTING', '2'),
                    $this->variant('', '3'),
                ])
            ),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::sequence()
                ->push(['data' => [['sku_code' => 'BANANA-EXISTING']]])
                ->push(['data' => ['sku_code' => 'BANANA-NEW']]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--execute' => true])
            ->expectsOutputToContain('Created: 1, would create: 0, skipped: 1, invalid: 1, failed: 0')
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus'
                && $request['sku_code'] === 'BANANA-NEW';
        });

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus'
                && $request['sku_code'] === 'BANANA-EXISTING';
        });
    }

    public function test_it_returns_failure_when_tracezilla_rejects_an_sku(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(
                $this->shopifyVariantsResponse([
                    $this->variant('INVALID-SKU', '1'),
                ])
            ),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::sequence()
                ->push(['data' => []])
                ->push(['message' => 'The SKU payload is invalid.'], 422),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--execute' => true])
            ->expectsOutputToContain('Created: 0, would create: 0, skipped: 0, invalid: 0, failed: 1')
            ->assertFailed();
    }

    public function test_the_default_dry_run_respects_the_limit_and_sends_no_write_request(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response(
                $this->shopifyVariantsResponse([
                    $this->variant('BANANA-001', '1'),
                    $this->variant('BANANA-002', '2'),
                ])
            ),
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [],
            ]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--limit' => 1])
            ->expectsOutputToContain('DRY RUN: no tracezilla SKUs will be created.')
            ->expectsOutputToContain('Shopify variants: 2 returned, 1 selected, 1 processed.')
            ->expectsOutputToContain('Created: 0, would create: 1, skipped: 0, invalid: 0, failed: 0')
            ->assertSuccessful();

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus';
        });
    }

    public function test_it_rejects_an_invalid_limit_before_synchronizing(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--limit' => 0])
            ->expectsOutputToContain('The --limit option must be a positive integer.')
            ->assertExitCode(2);

        Http::assertNotSent(function (Request $request): bool {
            return str_contains($request->url(), '/graphql.json')
                || str_contains($request->url(), '/api/v1/test-team/skus');
        });
    }

    public function test_production_execution_requires_confirmation(): void
    {
        $this->configureTestServices();
        $this->app->detectEnvironment(fn (): string => 'production');

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'test-shopify-token',
            ]),
        ]);

        $this->artisan('tracezilla:skus-from-shopify', ['--execute' => true])
            ->expectsConfirmation(
                'This will create missing SKUs in production tracezilla. Continue?',
                'no',
            )
            ->expectsOutputToContain('Execution cancelled. No data was modified.')
            ->assertFailed();

        Http::assertNotSent(function (Request $request): bool {
            return str_contains($request->url(), '/graphql.json')
                || str_contains($request->url(), '/api/v1/test-team/skus');
        });
    }

    public function test_shopify_authentication_failure_throws_an_exception(): void
    {
        $this->configureTestServices();

        Http::preventStrayRequests();

        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'errors' => 'Invalid API credentials',
            ], 401),
        ]);

        $this->expectException(RequestException::class);

        $this->artisan('tracezilla:skus-from-shopify')->run();
    }

    private function configureTestServices(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'test-client-id',
            'services.shopify.client_secret' => 'test-client-secret',
            'services.shopify.scope' => 'read_products',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'test-team',
            'services.tracezilla.api_key' => 'test-api-key',
        ]);
    }

    private function variant(string $sku, string $id): array
    {
        return [
            'id' => "gid://shopify/ProductVariant/{$id}",
            'legacyResourceId' => $id,
            'sku' => $sku,
            'price' => '10.00',
        ];
    }

    private function shopifyVariantsResponse(
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
