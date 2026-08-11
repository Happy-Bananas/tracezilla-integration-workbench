<?php

namespace Tests\Feature;

use App\Features\CatalogSynchronization\Data\TracezillaSkuData;
use App\Services\TracezillaSkuService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaSkuServiceTest extends TestCase
{
    public function test_it_returns_sku_codes_from_tracezilla(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [
                    ['sku_code' => 'BANANA-001'],
                    ['sku_code' => 'BANANA-002'],
                    ['sku_code' => null],
                ],
            ]),
        ]);

        $skuCodes = app(TracezillaSkuService::class)->getSkuCodes();

        $this->assertSame([
            'BANANA-001',
            'BANANA-002',
        ], $skuCodes);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus?sortBy=sku_code&sortDirection=asc&perPage=250'
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_it_creates_a_tracezilla_sku(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus' => Http::response([
                'data' => [
                    'id' => 123,
                    'sku_code' => 'BANANA-001',
                    'global_name' => 'Organic Banana',
                ],
            ], 201),
        ]);

        $sku = new TracezillaSkuData(
            skuCode: 'BANANA-001',
            globalName: 'Organic Banana',
            weightFactorNet: 1.0,
            weightFactorGross: 1.1,
            unitOfMeasure: 'pcs',
            lotUnit: 'colli',
            defaultUomConversion: 1.0,
        );

        $response = app(TracezillaSkuService::class)->createSku($sku);

        $this->assertSame('BANANA-001', $response['data']['sku_code']);

        Http::assertSent(function (Request $request) use ($sku): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus'
                && $request->data() === $sku->toApiPayload()
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_it_returns_sku_codes_from_all_tracezilla_pages(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::sequence()
                ->push([
                    'data' => [
                        ['sku_code' => 'BANANA-001'],
                    ],
                    'links' => [
                        'next_page' => 'https://tracezilla.test/api/v1/test-team/skus?page=2',
                    ],
                ])
                ->push([
                    'data' => [
                        ['sku_code' => 'BANANA-002'],
                        ['sku_code' => 'BANANA-001'],
                    ],
                    'links' => [
                        'next_page' => null,
                    ],
                ]),
        ]);

        $skuCodes = app(TracezillaSkuService::class)->getSkuCodes();

        $this->assertSame([
            'BANANA-001',
            'BANANA-002',
        ], $skuCodes);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_contains($request->url(), 'page=2');
        });
    }

    public function test_it_lists_the_requested_number_of_tracezilla_skus(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [
                    ['sku_code' => 'BANANA-001'],
                    ['sku_code' => 'BANANA-002'],
                ],
            ]),
        ]);

        $skus = app(TracezillaSkuService::class)->listSkus(2);

        $this->assertSame([
            ['sku_code' => 'BANANA-001'],
            ['sku_code' => 'BANANA-002'],
        ], $skus);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus?sortBy=sku_code&sortDirection=asc&perPage=2';
        });
    }

    public function test_it_throws_an_exception_when_tracezilla_rejects_the_request(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'message' => 'Unauthenticated.',
            ], 401),
        ]);

        $this->expectException(RequestException::class);

        app(TracezillaSkuService::class)->listSkus();
    }

    private function configureTracezilla(): void
    {
        config([
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'test-team',
            'services.tracezilla.api_key' => 'test-api-key',
        ]);
    }
}
