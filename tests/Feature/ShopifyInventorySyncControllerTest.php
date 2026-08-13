<?php

namespace Tests\Feature;

use App\Services\ShopifyInventoryService;
use App\Services\TracezillaInventoryService;
use Mockery;
use Tests\TestCase;

class ShopifyInventorySyncControllerTest extends TestCase
{
    public function test_page_warns_and_disables_controls_when_credentials_are_missing(): void
    {
        config(['services.shopify.client_secret' => null, 'services.tracezilla.api_key' => null]);

        $this->get('/shopify/inventory-from-tracezilla')
            ->assertOk()
            ->assertSee('Configuration required:')
            ->assertSee('Run inventory synchronization')
            ->assertSee('disabled', false);
    }

    public function test_page_defaults_to_a_bounded_dry_run_and_displays_mapping(): void
    {
        $this->configureServices();

        $this->get('/shopify/inventory-from-tracezilla')
            ->assertOk()
            ->assertSee('name="dry_run" value="1" checked', false)
            ->assertSee('name="limit" min="1" value="10"', false)
            ->assertSee('gid://shopify/Location/115073778028')
            ->assertSee('Location number <code>2</code>', false);
    }

    public function test_write_requires_server_side_confirmation(): void
    {
        $this->configureServices();

        $this->from('/shopify/inventory-from-tracezilla')
            ->post('/shopify/inventory-from-tracezilla')
            ->assertRedirect('/shopify/inventory-from-tracezilla')
            ->assertSessionHasErrors('confirmation');
    }

    public function test_dry_run_returns_structured_output(): void
    {
        $this->configureServices();
        $tracezilla = Mockery::mock(TracezillaInventoryService::class);
        $tracezilla->shouldReceive('getWarehouseInventory')->once()->with(2)->andReturn([]);
        $shopify = Mockery::mock(ShopifyInventoryService::class);
        $shopify->shouldReceive('getInventoryBySku')->once()->andReturn([]);
        $this->app->instance(TracezillaInventoryService::class, $tracezilla);
        $this->app->instance(ShopifyInventoryService::class, $shopify);

        $this->post('/shopify/inventory-from-tracezilla', ['dry_run' => '1', 'limit' => '10'])
            ->assertOk()
            ->assertSee('Dry run completed.')
            ->assertSee('would update: 0');
    }

    public function test_shopify_page_links_to_inventory_synchronization(): void
    {
        $this->get('/shopify')->assertOk()->assertSee(route('shopify.inventory-sync'));
    }

    private function configureServices(): void
    {
        config([
            'services.shopify.shop_url' => 'shop.myshopify.com',
            'services.shopify.client_id' => 'client-id',
            'services.shopify.client_secret' => 'client-secret',
            'services.shopify.scope' => 'read_inventory,write_inventory',
            'services.shopify.api_version' => '2025-10',
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'team',
            'services.tracezilla.api_key' => 'api-key',
        ]);
    }
}
