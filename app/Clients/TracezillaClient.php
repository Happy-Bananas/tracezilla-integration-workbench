<?php

namespace App\Clients;

use App\Clients\Exceptions\ClientConfigurationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TracezillaClient
{
    /**
     * Tracezilla base URL.
     */
    protected string $baseUrl;

    /**
     * Tracezilla team slug.
     */
    protected string $teamSlug;

    /**
     * Tracezilla API key.
     */
    protected string $apiKey;

    protected int $timeout;

    protected int $connectTimeout;

    /**
     * Authenticated HTTP client.
     */
    protected PendingRequest $http;

    public function __construct()
    {
        $config = config('services.tracezilla', []);

        $this->baseUrl = rtrim(
            $this->requiredString($config, 'base_url'),
            '/'
        );
        $this->teamSlug = $this->requiredString($config, 'team_slug');
        $this->apiKey = $this->requiredString($config, 'api_key');
        $this->timeout = $this->positiveInteger($config, 'timeout');
        $this->connectTimeout = $this->positiveInteger($config, 'connect_timeout');

        $this->http = Http::baseUrl(
            "{$this->baseUrl}/api/v1/{$this->teamSlug}"
        )
            ->acceptJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->withToken($this->apiKey);
    }

    /**
     * Get authenticated HTTP client.
     */
    public function http(): PendingRequest
    {
        return $this->http;
    }

    private function requiredString(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw ClientConfigurationException::missing("services.tracezilla.{$key}");
        }

        return trim($value);
    }

    private function positiveInteger(array $config, string $key): int
    {
        $value = filter_var($config[$key] ?? null, FILTER_VALIDATE_INT);

        if ($value === false || $value < 1) {
            throw ClientConfigurationException::invalidPositiveInteger(
                "services.tracezilla.{$key}"
            );
        }

        return $value;
    }
}
