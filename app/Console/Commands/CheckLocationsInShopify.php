<?php

namespace App\Console\Commands;

use App\Features\ShopifyLocations\Actions\ListShopifyLocations;
use Illuminate\Console\Command;

/**
 * List the Shopify locations visible to the configured app; this command is read-only.
 *
 * Usage: php artisan shopify:locations [--json]
 */
class CheckLocationsInShopify extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shopify:locations
        {--json : Output locations as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'List all locations available to the Shopify app';

    /**
     * Execute the console command.
     */
    public function handle(ListShopifyLocations $listLocations): int
    {
        $this->info('Fetching locations from Shopify...');

        $result = $listLocations->run();

        if ($this->option('json')) {
            $this->line(json_encode(
                $result->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info(sprintf('%d location(s) returned.', $result->count()));

        if ($result->isEmpty()) {
            $this->warn('No Shopify locations are available to this app.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Name', 'Status', 'Inventory', 'Online orders', 'GraphQL ID', 'Legacy ID', 'Address'],
            array_map(
                static fn ($location): array => [
                    $location->name,
                    $location->isActive ? 'Active' : 'Inactive',
                    $location->hasActiveInventory ? 'Yes' : 'No',
                    $location->fulfillsOnlineOrders ? 'Yes' : 'No',
                    $location->graphQlId,
                    $location->legacyId,
                    $location->formattedAddress() ?: '—',
                ],
                $result->locations,
            ),
        );

        return self::SUCCESS;
    }
}
