<?php

namespace App\Console\Commands;

use App\Features\CatalogSynchronization\Actions\SynchronizeCatalog;
use App\Features\CatalogSynchronization\Options\CatalogSyncOptions;
use App\Features\CatalogSynchronization\Results\CatalogSyncResult;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Preview or create tracezilla SKUs for missing Shopify variant SKUs.
 *
 * The command is a dry run by default. --execute enables creation after the
 * example unit, weight, and conversion mapping has been reviewed.
 *
 * Usage: php artisan tracezilla:skus-from-shopify [--limit=10]
 *        php artisan tracezilla:skus-from-shopify --execute --limit=1
 */
class TracezillaSkusFromShopifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tracezilla:skus-from-shopify
        {--execute : Create missing SKUs in tracezilla}
        {--limit= : Process at most this many Shopify variants}';

    /**
     * The console command description.
     */
    protected $description = 'Preview or create tracezilla SKUs from Shopify product variants';

    /**
     * Execute the console command.
     */
    public function handle(SynchronizeCatalog $synchronizeCatalog): int
    {
        try {
            $options = $this->syncOptions();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        if (! $this->confirmProductionExecution($options)) {
            $this->warn('Execution cancelled. No data was modified.');

            return self::FAILURE;
        }

        if ($options->dryRun) {
            $this->warn('DRY RUN: no tracezilla SKUs will be created.');
        } else {
            $this->warn('EXECUTION ENABLED: missing tracezilla SKUs may be created.');
        }

        $result = $synchronizeCatalog->run($options);

        $this->displayResult($result);

        return $result->hasFailures()
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function syncOptions(): CatalogSyncOptions
    {
        $limitOption = $this->option('limit');
        $limit = null;

        if ($limitOption !== null) {
            $limitValue = filter_var($limitOption, FILTER_VALIDATE_INT);

            if ($limitValue === false || $limitValue < 1) {
                throw new InvalidArgumentException(
                    'The --limit option must be a positive integer.'
                );
            }

            $limit = $limitValue;
        }

        return $this->option('execute')
            ? CatalogSyncOptions::execute($limit)
            : CatalogSyncOptions::dryRun($limit);
    }

    private function confirmProductionExecution(CatalogSyncOptions $options): bool
    {
        if (! $options->willExecute() || ! app()->environment('production')) {
            return true;
        }

        return $this->confirm(
            'This will create missing SKUs in production tracezilla. Continue?',
            false,
        );
    }

    private function displayResult(CatalogSyncResult $result): void
    {
        $summary = $result->summary();

        $this->newLine();
        $this->line(sprintf(
            'Shopify variants: %d returned, %d selected, %d processed.',
            $summary['source_count'],
            $summary['selected_count'],
            $summary['processed_count'],
        ));
        $this->line(sprintf(
            'Created: %d, would create: %d, skipped: %d, invalid: %d, failed: %d',
            $summary['created_count'],
            $summary['would_create_count'],
            $summary['skipped_count'],
            $summary['invalid_count'],
            $summary['failed_count'],
        ));

        $this->newLine();
        $this->line(json_encode(
            $result->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));
    }
}
