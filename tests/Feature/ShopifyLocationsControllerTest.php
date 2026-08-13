<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyLocationsControllerTest extends TestCase
{
    public function test_shopify_page_links_to_the_locations_page(): void
    {
        $this->get('/shopify')
            ->assertOk()
            ->assertSee(route('shopify.locations'), false)
            ->assertSee('List Shopify Locations');
    }

    public function test_page_disables_the_button_when_credentials_are_missing(): void
    {
        config(['services.shopify.client_secret' => null]);

        $this->get('/shopify/locations')
            ->assertOk()
            ->assertSee('Configuration required:')
            ->assertSee('List Shopify Locations')
            ->assertSee('disabled', false);
    }

    public function test_it_lists_locations_using_the_existing_action(): void
    {
        config([
            'services.shopify.shop_url' => 'test-shop.myshopify.com',
            'services.shopify.client_id' => 'client-id',
            'services.shopify.client_secret' => 'client-secret',
            'services.shopify.scope' => 'read_locations',
            'services.shopify.api_version' => '2025-10',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response(['access_token' => 'token']),
            'https://test-shop.myshopify.com/admin/api/2025-10/graphql.json' => Http::response([
                'data' => ['locations' => [
                    'nodes' => [[
                        'id' => 'gid://shopify/Location/1',
                        'legacyResourceId' => '1',
                        'name' => 'Development Warehouse',
                        'isActive' => true,
                        'hasActiveInventory' => true,
                        'fulfillsOnlineOrders' => true,
                        'address' => ['address1' => 'Banana Street 1', 'address2' => null, 'city' => 'Copenhagen', 'province' => null, 'country' => 'Denmark', 'zip' => '1000'],
                    ]],
                    'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                ]],
            ]),
        ]);

        $this->post('/shopify/locations')
            ->assertOk()
            ->assertSee('1 Shopify location(s) returned.')
            ->assertSee('Development Warehouse')
            ->assertSee('gid://shopify/Location/1');
    }
}
