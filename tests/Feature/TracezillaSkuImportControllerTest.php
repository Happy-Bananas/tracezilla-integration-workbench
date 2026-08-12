<?php

namespace Tests\Feature;

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
