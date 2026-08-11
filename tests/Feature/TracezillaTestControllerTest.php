<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaTestControllerTest extends TestCase
{
    private array $credentials = [
        'base_url' => 'https://tracezilla.test',
        'team_slug' => 'banana-team',
        'api_key' => 'private-api-key',
    ];

    public function test_credentials_are_validated_saved_and_never_rendered(): void
    {
        Http::fake(['*/skus*' => Http::response(['data' => []])]);

        $response = $this->post('/tracezilla/test', $this->credentials);

        $response->assertOk()->assertSee('Tracezilla credentials are valid.')
            ->assertSessionHas('workbench.tracezilla.api_key', 'private-api-key')
            ->assertDontSee('private-api-key')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_saved_credentials_enable_read_only_sku_check(): void
    {
        Http::fake(['*/skus*' => Http::response(['data' => [['sku_code' => 'BANANA-001']]])]);

        $response = $this->withSession(['workbench.tracezilla' => $this->credentials + ['timeout' => 30, 'connect_timeout' => 10]])
            ->post('/tracezilla/list-skus');

        $response->assertOk()->assertSee('BANANA-001')->assertDontSee('private-api-key');
    }

    public function test_credentials_can_be_forgotten(): void
    {
        $this->withSession(['workbench.tracezilla' => $this->credentials])
            ->delete('/tracezilla/credentials')
            ->assertRedirect('/tracezilla')
            ->assertSessionMissing('workbench.tracezilla');
    }
}
