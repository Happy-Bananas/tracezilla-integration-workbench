<?php

namespace App\Clients;

use App\Clients\Exceptions\ClientConfigurationException;
use App\Clients\Exceptions\ShopifyAuthenticationException;
use App\Clients\Exceptions\ShopifyGraphQlException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ShopifyClient
{
    /**
     * Shopify shop URL.
     */
    protected string $shopUrl;

    /**
     * Shopify client ID.
     */
    protected string $clientId;

    /**
     * Shopify client secret.
     */
    protected string $clientSecret;

    protected string $apiVersion;

    protected int $timeout;

    protected int $connectTimeout;

    /**
     * Authenticated HTTP client.
     */
    protected PendingRequest $http;

    public function __construct()
    {
        $config = config('services.shopify', []);

        $this->shopUrl = $this->shopDomain(
            $this->requiredString($config, 'shop_url')
        );
        $this->clientId = $this->requiredString($config, 'client_id');
        $this->clientSecret = $this->requiredString($config, 'client_secret');
        $this->apiVersion = $this->requiredString($config, 'api_version');
        $this->timeout = $this->positiveInteger($config, 'timeout');
        $this->connectTimeout = $this->positiveInteger($config, 'connect_timeout');

        $response = Http::asForm()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->post(
                "https://{$this->shopUrl}/admin/oauth/access_token",
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => $this->requiredString($config, 'scope'),
                ]
            );

        $payload = $response->throw()->json();

        if (! is_array($payload) || empty($payload['access_token'])) {
            throw new ShopifyAuthenticationException(
                'Shopify authentication response did not contain an access token.'
            );
        }

        $this->http = Http::baseUrl(
            "https://{$this->shopUrl}/admin/api/{$this->apiVersion}"
        )
            ->acceptJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->withHeaders([
            'X-Shopify-Access-Token' => $payload['access_token'],
        ]);
    }

    /**
     * Get authenticated HTTP client.
     */
    public function http(): PendingRequest
    {
        return $this->http;
    }

    public function graphql(string $query, array $variables = []): array
    {
        return $this->decodeGraphqlResponse(
            $this->sendGraphqlRequest($query, $variables)
        );
    }

    /**
     * Verify authenticated API access and report the version Shopify used.
     *
     * @return array{shop_name: string|null, requested_api_version: string, actual_api_version: string|null}
     */
    public function connectionStatus(): array
    {
        $response = $this->sendGraphqlRequest(<<<'GRAPHQL'
            query ConnectionStatus {
                shop {
                    name
                }
            }
            GRAPHQL);

        $payload = $this->decodeGraphqlResponse($response);
        $actualApiVersion = $response->header('X-Shopify-API-Version');

        return [
            'shop_name' => data_get($payload, 'data.shop.name'),
            'requested_api_version' => $this->apiVersion,
            'actual_api_version' => is_string($actualApiVersion) && $actualApiVersion !== ''
                ? $actualApiVersion
                : null,
        ];
    }

    private function sendGraphqlRequest(string $query, array $variables = []): Response
    {
        $payload = [
            'query' => $query,
        ];

        if (! empty($variables)) {
            $payload['variables'] = $variables;
        }

        return $this->http
            ->post('/graphql.json', $payload)
            ->throw();
    }

    private function decodeGraphqlResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw ShopifyGraphQlException::invalidResponse();
        }

        if (! empty($payload['errors'])) {
            $errors = is_array($payload['errors'])
                ? $payload['errors']
                : [$payload['errors']];

            throw ShopifyGraphQlException::fromErrors($errors);
        }

        return $payload;
    }

    private function requiredString(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw ClientConfigurationException::missing("services.shopify.{$key}");
        }

        return trim($value);
    }

    private function positiveInteger(array $config, string $key): int
    {
        $value = filter_var($config[$key] ?? null, FILTER_VALIDATE_INT);

        if ($value === false || $value < 1) {
            throw ClientConfigurationException::invalidPositiveInteger(
                "services.shopify.{$key}"
            );
        }

        return $value;
    }

    private function shopDomain(string $shopUrl): string
    {
        return rtrim(
            preg_replace('#^https?://#i', '', $shopUrl),
            '/'
        );
    }
}
