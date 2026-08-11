<?php

namespace App\Console\Commands;

use App\Features\InventorySynchronization\Actions\SynchronizeInventory;
use App\Features\InventorySynchronization\Options\InventorySyncOptions;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Preview or set Shopify available inventory from a tracezilla warehouse.
 *
 * The command is a dry run by default. --execute writes absolute quantities
 * using Shopify compare quantities to reject stale updates.
 *
 * Usage: php artisan shopify:inventory-from-tracezilla [--limit=10]
 *        php artisan shopify:inventory-from-tracezilla --execute --limit=1
 */
class UpdateInventoryInShopify extends Command
{
    protected $signature = 'shopify:inventory-from-tracezilla
        {--execute : Write changed quantities to Shopify}
        {--limit= : Process at most this many Tracezilla inventory records}';

    protected $description = 'Synchronize Shopify inventory from Tracezilla (dry-run by default)';

    public function handle(SynchronizeInventory $synchronize): int
    {
        try {
            $options = $this->syncOptions();

            if (! $this->confirmProductionExecution($options)) {
                $this->warn('Execution cancelled. No data was modified.');

                return self::FAILURE;
            }

            $result = $synchronize->run($options);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($options->dryRun
            ? 'DRY RUN: no Shopify inventory was changed.'
            : 'EXECUTE: changed quantities were written to Shopify.');

        foreach ($result->items() as $item) {
            $this->line(sprintf(
                '[%s] %s: %s',
                strtoupper($item->status->value),
                $item->sku,
                $item->message,
            ));
        }

        $summary = $result->summary();
        $this->newLine();
        $this->info(sprintf(
            'Updated: %d, would update: %d, unchanged: %d, skipped: %d, failed: %d',
            $summary['updated'],
            $summary['would_update'],
            $summary['unchanged'],
            $summary['skipped'],
            $summary['failed'],
        ));

        return $result->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    private function syncOptions(): InventorySyncOptions
    {
        $limit = $this->option('limit');

        if ($limit !== null && filter_var($limit, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('The --limit option must be a positive integer.');
        }

        return new InventorySyncOptions(
            dryRun: ! (bool) $this->option('execute'),
            limit: $limit === null ? null : (int) $limit,
        );
    }

    private function confirmProductionExecution(InventorySyncOptions $options): bool
    {
        if (! $options->willExecute() || ! app()->environment('production')) {
            return true;
        }

        return $this->confirm(
            'This will replace available quantities at the configured production Shopify location. Continue?',
            false,
        );
    }
}
