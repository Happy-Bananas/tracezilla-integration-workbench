<?php

namespace App\Http\Controllers;

use App\Features\CatalogSynchronization\Actions\SynchronizeCatalog;
use App\Features\CatalogSynchronization\Options\CatalogSyncOptions;
use Illuminate\Http\Request;
use Throwable;

class TracezillaSkuImportController extends Controller
{
    public function show()
    {
        return view('tracezilla.sku-import', $this->viewData());
    }

    public function run(Request $request)
    {
        $configured = $this->configurationStatus();

        if (! $configured['ready']) {
            return view('tracezilla.sku-import', $this->viewData(
                error: 'Configure both Shopify and Tracezilla credentials in .env before running the import.',
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
                'confirmation' => 'You must confirm that Tracezilla will be updated.',
            ])->withInput();
        }

        $options = $dryRun
            ? CatalogSyncOptions::dryRun($validated['limit'] ?? null)
            : CatalogSyncOptions::execute($validated['limit'] ?? null);

        try {
            $synchronizeCatalog = app(SynchronizeCatalog::class);
            $result = $synchronizeCatalog->run($options);

            return view('tracezilla.sku-import', $this->viewData(
                result: $result->toArray(),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return view('tracezilla.sku-import', $this->viewData(
                error: 'The catalog synchronization could not be completed. Check the credentials and application log.',
            ));
        }
    }

    private function viewData(?array $result = null, ?string $error = null): array
    {
        return [
            'configuration' => $this->configurationStatus(),
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

        return [
            'shopify' => $shopifyReady,
            'tracezilla' => $tracezillaReady,
            'ready' => $shopifyReady && $tracezillaReady,
        ];
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
