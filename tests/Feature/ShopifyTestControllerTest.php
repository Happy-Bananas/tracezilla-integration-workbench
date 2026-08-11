<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyTestControllerTest extends TestCase
{
    private array $credentials = [
        'shop_url' => 'test-shop.myshopify.com',
        'client_id' => 'client-id',
        'client_secret' => 'super-secret',
        'scope' => 'read_products,read_locations',
        'api_version' => '2026-07',
    ];

    public function test_credentials_are_validated_saved_and_never_rendered(): void
    {
        Http::fake([
            '*/admin/oauth/access_token' => Http::response(['access_token' => 'private-token']),
            '*/graphql.json' => Http::response(['data' => ['shop' => ['name' => 'Banana Shop']]], 200, ['X-Shopify-API-Version' => '2026-07']),
        ]);

        $response = $this->post('/shopify/test', $this->credentials);

        $response->assertOk()->assertSee('Shopify credentials are valid.')
            ->assertSessionHas('workbench.shopify.client_secret', 'super-secret')
            ->assertDontSee('super-secret')->assertDontSee('private-token')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_it_normalizes_a_pasted_shopify_admin_url(): void
    {
        Http::fake([
            'https://test-shop.myshopify.com/admin/oauth/access_token' => Http::response(['access_token' => 'private-token']),
            '*/graphql.json' => Http::response(['data' => ['shop' => ['name' => 'Banana Shop']]], 200, ['X-Shopify-API-Version' => '2026-07']),
        ]);

        $response = $this->post('/shopify/test', array_merge($this->credentials, [
            'shop_url' => 'https://TEST-SHOP.myshopify.com/admin/settings/apps',
            'scope' => 'read_products, read_locations',
        ]));

        $response->assertOk()
            ->assertSessionHas('workbench.shopify.shop_url', 'test-shop.myshopify.com')
            ->assertSessionHas('workbench.shopify.scope', 'read_products,read_locations');
    }

    public function test_it_shows_the_specific_validation_error(): void
    {
        $response = $this->from('/shopify')->post('/shopify/test', array_merge($this->credentials, [
            'shop_url' => 'not-a-shop-domain.example',
        ]));

        $response->assertRedirect('/shopify')
            ->assertSessionHasErrors('shop_url');

    }

    public function test_saved_credentials_enable_read_only_product_check(): void
    {
        Http::fake([
            '*/admin/oauth/access_token' => Http::response(['access_token' => 'private-token']),
            '*/graphql.json' => Http::response(['data' => ['products' => ['nodes' => [['title' => 'Bananas']]]]]),
        ]);

        $response = $this->withSession(['workbench.shopify' => $this->credentials + ['timeout' => 30, 'connect_timeout' => 10]])
            ->post('/shopify/list-products');

        $response->assertOk()->assertSee('Bananas')->assertDontSee('super-secret');
    }

    public function test_credentials_can_be_forgotten(): void
    {
        $this->withSession(['workbench.shopify' => $this->credentials])
            ->delete('/shopify/credentials')
            ->assertRedirect('/shopify')
            ->assertSessionMissing('workbench.shopify');
    }
}
