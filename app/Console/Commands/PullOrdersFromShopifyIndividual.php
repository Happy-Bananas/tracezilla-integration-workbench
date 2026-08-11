<?php

namespace App\Console\Commands;

use App\Features\OrderSynchronization\Actions\SynchronizeOrders;
use App\Features\OrderSynchronization\Options\OrderSyncOptions;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Preview or create one tracezilla sales order per recent Shopify order.
 *
 * The command is a dry run by default. --execute enables creation, while
 * duplicate external references, cancelled orders, and unsafe input are skipped.
 *
 * Usage: php artisan tracezilla:orders-from-shopify [--days=3] [--limit=1]
 *        php artisan tracezilla:orders-from-shopify --execute --limit=1
 */
class PullOrdersFromShopifyIndividual extends Command
{
    protected $signature = 'tracezilla:orders-from-shopify
        {--execute : Create sales orders in tracezilla}
        {--days=3 : Read Shopify orders created within this many days}
        {--limit= : Process at most this many Shopify orders}';

    protected $description = 'Create one tracezilla sales order per Shopify order (dry-run by default)';

    public function handle(): int
    {
        try {
            $options = $this->syncOptions();

            if (! $this->confirmProductionExecution($options)) {
                $this->warn('Execution cancelled. No data was modified.');

                return self::FAILURE;
            }

            /*
             * Resolve inside the try block so client authentication and
             * configuration failures are rendered as one safe console error.
             */
            $result = app(SynchronizeOrders::class)->run($options);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($options->dryRun
            ? 'DRY RUN: no tracezilla sales orders were created.'
            : 'EXECUTE: new tracezilla sales orders were created.');

        foreach ($result->items() as $item) {
            $reference = $item->externalReference === null ? '' : " ({$item->externalReference})";
            $this->line(sprintf(
                '[%s] %s%s: %s',
                strtoupper($item->status->value),
                $item->shopifyOrder,
                $reference,
                $item->message,
            ));
        }

        $summary = $result->summary();
        $this->newLine();
        $this->info(sprintf(
            'Created: %d, would create: %d, skipped: %d, failed: %d',
            $summary['created'],
            $summary['would_create'],
            $summary['skipped'],
            $summary['failed'],
        ));

        return $result->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    private function syncOptions(): OrderSyncOptions
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        $limit = $this->option('limit');

        if ($days === false || $days < 1) {
            throw new InvalidArgumentException('The --days option must be a positive integer.');
        }

        if ($limit !== null
            && (filter_var($limit, FILTER_VALIDATE_INT) === false || (int) $limit < 1)) {
            throw new InvalidArgumentException('The --limit option must be a positive integer.');
        }

        return new OrderSyncOptions(
            dryRun: ! (bool) $this->option('execute'),
            days: (int) $days,
            limit: $limit === null ? null : (int) $limit,
        );
    }

    private function confirmProductionExecution(OrderSyncOptions $options): bool
    {
        if (! $options->willExecute() || ! app()->environment('production')) {
            return true;
        }

        return $this->confirm(
            'This will create sales orders in the configured production tracezilla account. Continue?',
            false,
        );
    }
}
