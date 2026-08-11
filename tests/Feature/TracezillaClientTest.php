<?php

namespace Tests\Feature;

use App\Clients\Exceptions\ClientConfigurationException;
use App\Clients\TracezillaClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaClientTest extends TestCase
{
    public function test_it_builds_an_authenticated_team_api_client(): void
    {
        $this->configureTracezilla([
            'base_url' => 'https://tracezilla.test/',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://tracezilla.test/api/v1/test-team/ping' => Http::response([
                'status' => 'ok',
            ]),
        ]);

        $response = app(TracezillaClient::class)
            ->http()
            ->get('/ping')
            ->throw()
            ->json();

        $this->assertSame('ok', $response['status']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://tracezilla.test/api/v1/test-team/ping'
                && $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_it_rejects_missing_configuration_before_sending_a_request(): void
    {
        $this->configureTracezilla([
            'api_key' => null,
        ]);

        Http::preventStrayRequests();

        $this->expectException(ClientConfigurationException::class);
        $this->expectExceptionMessage(
            'Missing required client configuration [services.tracezilla.api_key].'
        );

        app(TracezillaClient::class);
    }

    public function test_it_rejects_an_invalid_timeout(): void
    {
        $this->configureTracezilla([
            'timeout' => 0,
        ]);

        Http::preventStrayRequests();

        $this->expectException(ClientConfigurationException::class);
        $this->expectExceptionMessage(
            'Client configuration [services.tracezilla.timeout] must be a positive integer.'
        );

        app(TracezillaClient::class);
    }

    private function configureTracezilla(array $overrides = []): void
    {
        config([
            'services.tracezilla' => array_merge([
                'base_url' => 'https://tracezilla.test',
                'team_slug' => 'test-team',
                'api_key' => 'test-api-key',
                'timeout' => 30,
                'connect_timeout' => 10,
            ], $overrides),
        ]);
    }
}
