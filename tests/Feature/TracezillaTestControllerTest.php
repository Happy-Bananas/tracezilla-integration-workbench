<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaTestControllerTest extends TestCase
{
    public function test_it_displays_tracezilla_configuration_without_revealing_the_api_key(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        $response = $this->get('/tracezilla');

        $response
            ->assertOk()
            ->assertViewIs('tracezilla.test')
            ->assertViewHas('config')
            ->assertSee('tracezilla Connection Test')
            ->assertSee('https://tracezilla.test')
            ->assertSee('test-team')
            ->assertSee('✅ Configured')
            ->assertDontSee('test-api-key');

        Http::assertNothingSent();
    }

    public function test_it_reports_a_successful_tracezilla_connection(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [
                    ['sku_code' => 'PRIVATE-TEST-SKU'],
                ],
            ]),
        ]);

        $response = $this->post('/tracezilla/test');

        $response
            ->assertOk()
            ->assertViewIs('tracezilla.test')
            ->assertViewHas('result.message', 'Successfully connected to the Tracezilla API.')
            ->assertViewHas('result', function (array $result): bool {
                return ! array_key_exists('response', $result);
            })
            ->assertViewHas('error', null)
            ->assertSee('Success:')
            ->assertSee('Successfully connected to the Tracezilla API.')
            ->assertDontSee('PRIVATE-TEST-SKU')
            ->assertDontSee('test-api-key');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus?perPage=1'
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_it_displays_a_safe_error_when_tracezilla_connection_fails(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'message' => 'Unauthenticated.',
            ], 401),
        ]);

        $response = $this->post('/tracezilla/test');

        $response
            ->assertOk()
            ->assertViewIs('tracezilla.test')
            ->assertViewHas('result', null)
            ->assertViewHas('error', function ($error): bool {
                return is_string($error)
                    && str_contains($error, '401');
            })
            ->assertSee('Error:')
            ->assertSee('401')
            ->assertDontSee('test-api-key');
    }

    public function test_it_displays_skus_returned_by_tracezilla(): void
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

        $response = $this->post('/tracezilla/list-skus');

        $response
            ->assertOk()
            ->assertViewIs('tracezilla.test')
            ->assertViewHas('result.message', '2 Tracezilla SKU(s) returned.')
            ->assertViewHas('error', null)
            ->assertSee('2 Tracezilla SKU(s) returned.')
            ->assertSee('BANANA-001')
            ->assertSee('BANANA-002')
            ->assertDontSee('test-api-key');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/skus?sortBy=sku_code&sortDirection=asc&perPage=10';
        });
    }

    public function test_it_displays_a_clear_message_when_tracezilla_has_no_skus(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'data' => [],
            ]),
        ]);

        $response = $this->post('/tracezilla/list-skus');

        $response
            ->assertOk()
            ->assertViewHas('result.message', 'No SKUs found in Tracezilla.')
            ->assertViewHas('result.response', [])
            ->assertViewHas('error', null)
            ->assertSee('No SKUs found in Tracezilla.');
    }

    public function test_it_displays_a_safe_error_when_tracezilla_sku_listing_fails(): void
    {
        $this->configureTracezilla();

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/skus*' => Http::response([
                'message' => 'Service unavailable.',
            ], 503),
        ]);

        $response = $this->post('/tracezilla/list-skus');

        $response
            ->assertOk()
            ->assertViewIs('tracezilla.test')
            ->assertViewHas('result', null)
            ->assertViewHas('error', function ($error): bool {
                return is_string($error)
                    && str_contains($error, '503');
            })
            ->assertSee('Error:')
            ->assertSee('503')
            ->assertDontSee('test-api-key');
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
