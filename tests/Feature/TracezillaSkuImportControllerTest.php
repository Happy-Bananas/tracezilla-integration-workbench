<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaSkuImportControllerTest extends TestCase
{
    public function test_page_warns_and_disables_controls_when_credentials_are_missing(): void
    {
        config([
            'services.shopify.client_secret' => null,
            'services.tracezilla.api_key' => null,
        ]);

        $response = $this->get('/tracezilla/import-shopify-skus');

        $response->assertOk()
            ->assertSee('Configuration required:')
            ->assertSee('Run SKU import')
            ->assertSee('disabled', false);
    }

    public function test_write_requires_server_side_confirmation(): void
    {
        $this->configureServices();

        $response = $this->from('/tracezilla/import-shopify-skus')
            ->post('/tracezilla/import-shopify-skus');

        $response->assertRedirect('/tracezilla/import-shopify-skus')
            ->assertSessionHasErrors('confirmation');
    }

    public function test_page_defaults_to_dry_run(): void
    {
        $this->configureServices();

        $this->get('/tracezilla/import-shopify-skus')
            ->assertOk()
            ->assertSee('name="dry_run" value="1" checked', false);
    }

    public function test_dry_run_accepts_an_empty_write_confirmation(): void
    {
        $this->configureServices();
        Http::preventStrayRequests();
        Http::fake([
            'https://shop.myshopify.com/admin/oauth/access_token' => Http::response([
                'access_token' => 'shopify-token',
            ]),
            'https://shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => [
                    'productVariants' => [
                        'nodes' => [],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ]),
            'https://tracezilla.test/api/v1/team/skus*' => Http::response([
                'data' => [],
                'links' => ['next_page' => null],
            ]),
        ]);

        $response = $this->post('/tracezilla/import-shopify-skus', [
            'dry_run' => '1',
            'confirm_write' => '',
        ]);

        $response->assertOk()
            ->assertSee('Dry run completed.')
            ->assertDontSee('The selected confirm write is invalid.');

        Http::assertSentCount(3);
    }

    private function configureServices(): void
    {
        config([
            'services.shopify.shop_url' => 'shop.myshopify.com',
            'services.shopify.client_id' => 'client-id',
            'services.shopify.client_secret' => 'client-secret',
            'services.shopify.scope' => 'read_products',
            'services.shopify.api_version' => '2025-10',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'team',
            'services.tracezilla.api_key' => 'api-key',
        ]);
    }
}
