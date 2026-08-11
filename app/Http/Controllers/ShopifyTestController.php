<?php

namespace App\Http\Controllers;

use App\Clients\ShopifyClient;
use App\GraphQL\Queries\GetLocations;
use App\GraphQL\Queries\GetProducts;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class ShopifyTestController extends Controller
{
    public function show(Request $request)
    {
        return $this->page($request);
    }

    public function test(Request $request)
    {
        $credentials = $this->credentials($request);

        try {
            $status = (new ShopifyClient($credentials))->connectionStatus();

            if ($status['actual_api_version'] !== $status['requested_api_version']) {
                throw new RuntimeException(sprintf(
                    'Shopify used API version [%s] instead of requested version [%s].',
                    $status['actual_api_version'] ?? 'unknown',
                    $status['requested_api_version'],
                ));
            }

            $request->session()->put('workbench.shopify', $credentials);

            return $this->page($request, [
                'message' => 'Shopify credentials are valid.',
                'shop' => $status['shop_name'],
                'api_version' => $status['actual_api_version'],
            ]);
        } catch (Throwable) {
            return $this->page($request, error: 'Shopify could not authenticate or complete the API check. Verify the shop domain, credentials, scopes, and API version.');
        }
    }

    public function listProducts(Request $request)
    {
        return $this->graphqlList($request, GetProducts::QUERY, 'products', 'products');
    }

    public function listLocations(Request $request)
    {
        return $this->graphqlList($request, GetLocations::QUERY, 'locations', 'locations');
    }

    public function forget(Request $request)
    {
        $request->session()->forget('workbench.shopify');

        return redirect()->route('shopify.test')->with('status', 'Shopify credentials removed from this browser session.');
    }

    private function graphqlList(Request $request, string $query, string $key, string $viewKey)
    {
        $credentials = $request->session()->get('workbench.shopify');

        if (! is_array($credentials)) {
            return $this->page($request, error: 'Validate Shopify credentials before running a read-only check.');
        }

        try {
            $response = (new ShopifyClient($credentials))->graphql($query, ['first' => 10]);
            $items = data_get($response, "data.{$key}.nodes", []);

            return $this->page($request, data: [$viewKey => $items]);
        } catch (Throwable) {
            return $this->page($request, error: 'Shopify could not complete the read-only request. Check the app scopes and try again.');
        }
    }

    private function credentials(Request $request): array
    {
        $request->merge([
            'shop_url' => $this->normalizeShopDomain((string) $request->input('shop_url')),
            'scope' => preg_replace('/\s+/', '', (string) $request->input('scope')),
        ]);

        $validated = $request->validate([
            'shop_url' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9-]*\\.myshopify\\.com$/i'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:500'],
            'scope' => ['required', 'string', 'max:1000'],
            'api_version' => ['required', 'regex:/^20\\d{2}-(01|04|07|10)$/'],
        ]);

        return $validated + ['timeout' => 30, 'connect_timeout' => 10];
    }

    private function normalizeShopDomain(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('#^https?://#i', '', $value);

        return strtolower(explode('/', $value, 2)[0]);
    }

    private function page(Request $request, ?array $result = null, ?string $error = null, array $data = [])
    {
        $saved = $request->session()->get('workbench.shopify');

        return response()
            ->view('shopify.test', array_merge([
                'saved' => is_array($saved) ? $saved : null,
                'result' => $result,
                'products' => null,
                'locations' => null,
                'error' => $error,
            ], $data))
            ->header('Cache-Control', 'no-store, private');
    }
}
