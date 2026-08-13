<?php

namespace App\Http\Controllers;

use App\Features\ShopifyLocations\Actions\ListShopifyLocations;
use Throwable;

class ShopifyLocationsController extends Controller
{
    public function show()
    {
        return view('shopify.locations', $this->viewData());
    }

    public function run(ListShopifyLocations $listLocations)
    {
        if (! $this->isConfigured()) {
            return view('shopify.locations', $this->viewData(
                error: 'Configure the Shopify credentials in .env before listing locations.',
            ));
        }

        try {
            return view('shopify.locations', $this->viewData(
                result: $listLocations->run()->toArray(),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return view('shopify.locations', $this->viewData(
                error: 'Shopify locations could not be retrieved. Check the credentials, read_locations scope, and application log.',
            ));
        }
    }

    private function viewData(?array $result = null, ?string $error = null): array
    {
        return [
            'configuration' => [
                'shopify' => $this->isConfigured(),
                'scope' => config('services.shopify.scope'),
            ],
            'result' => $result,
            'error' => $error,
        ];
    }

    private function isConfigured(): bool
    {
        $configuration = config('services.shopify', []);

        foreach (['shop_url', 'client_id', 'client_secret', 'scope', 'api_version'] as $key) {
            if (! is_string($configuration[$key] ?? null) || trim($configuration[$key]) === '') {
                return false;
            }
        }

        return true;
    }
}
