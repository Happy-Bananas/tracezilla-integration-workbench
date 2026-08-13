<?php

namespace App\Http\Controllers;

use App\Features\InventorySynchronization\Actions\SynchronizeInventory;
use App\Features\InventorySynchronization\Options\InventorySyncOptions;
use Illuminate\Http\Request;
use Throwable;

class ShopifyInventorySyncController extends Controller
{
    public function show()
    {
        return view('shopify.inventory-sync', $this->viewData());
    }

    public function run(Request $request)
    {
        if (! $this->configurationStatus()['ready']) {
            return view('shopify.inventory-sync', $this->viewData(
                error: 'Configure both Shopify and Tracezilla credentials in .env before synchronizing inventory.',
            ));
        }

        $validated = $request->validate([
            'dry_run' => ['sometimes', 'accepted'],
            'confirm_write' => ['nullable', 'in:yes'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ]);
        $dryRun = $request->boolean('dry_run');

        if (! $dryRun && ($validated['confirm_write'] ?? null) !== 'yes') {
            return back()->withErrors([
                'confirmation' => 'You must confirm that Shopify inventory will be updated.',
            ])->withInput();
        }

        try {
            $synchronize = app(SynchronizeInventory::class);
            $result = $synchronize->run(new InventorySyncOptions(
                dryRun: $dryRun,
                limit: $validated['limit'] ?? null,
            ));

            return view('shopify.inventory-sync', $this->viewData(result: [
                'dry_run' => $dryRun,
                'summary' => $result->summary(),
                'items' => array_map(static fn ($item): array => [
                    'sku' => $item->sku,
                    'status' => $item->status->value,
                    'message' => $item->message,
                    'from' => $item->from,
                    'to' => $item->to,
                ], $result->items()),
            ]));
        } catch (Throwable $exception) {
            report($exception);

            return view('shopify.inventory-sync', $this->viewData(
                error: 'Inventory synchronization could not be completed. Check the credentials, mapping, and application log.',
            ));
        }
    }

    private function viewData(?array $result = null, ?string $error = null): array
    {
        return [
            'configuration' => $this->configurationStatus(),
            'mapping' => [
                'shopify_location_id' => SynchronizeInventory::SHOPIFY_LOCATION_ID,
                'tracezilla_warehouse_location_number' => SynchronizeInventory::TRACEZILLA_WAREHOUSE_LOCATION_NUMBER,
            ],
            'result' => $result,
            'error' => $error,
        ];
    }

    private function configurationStatus(): array
    {
        $shopify = config('services.shopify', []);
        $tracezilla = config('services.tracezilla', []);
        $shopifyReady = $this->hasValues($shopify, ['shop_url', 'client_id', 'client_secret', 'scope', 'api_version']);
        $tracezillaReady = $this->hasValues($tracezilla, ['base_url', 'team_slug', 'api_key']);

        return ['shopify' => $shopifyReady, 'tracezilla' => $tracezillaReady, 'ready' => $shopifyReady && $tracezillaReady];
    }

    private function hasValues(array $configuration, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! is_string($configuration[$key] ?? null) || trim($configuration[$key]) === '') {
                return false;
            }
        }

        return true;
    }
}
