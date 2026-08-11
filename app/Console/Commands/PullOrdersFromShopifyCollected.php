<?php

namespace App\Console\Commands;

use App\Features\CollectedOrderReporting\Actions\BuildCollectedOrderReport;
use App\Features\CollectedOrderReporting\Options\CollectedOrderReportOptions;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Build a read-only daily sales summary grouped by date, currency, and SKU.
 *
 * Summarized tracezilla order creation is intentionally disabled.
 *
 * Usage: php artisan pull-orders-from-shopify-collected
 *        [--days=3] [--timezone=UTC] [--limit=10] [--json]
 */
class PullOrdersFromShopifyCollected extends Command
{
    protected $signature = 'pull-orders-from-shopify-collected
        {--days=3 : Read orders created within this many days}
        {--timezone=UTC : IANA timezone defining the business day}
        {--limit= : Inspect at most this many orders}
        {--json : Output the structured report as JSON}';

    protected $description = 'Preview daily Shopify sales totals by SKU without writing';

    public function handle(BuildCollectedOrderReport $report): int
    {
        try {
            $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
            $rawLimit = $this->option('limit');
            $limit = $rawLimit === null ? null : filter_var($rawLimit, FILTER_VALIDATE_INT);
            if ($days === false || $days < 1) {
                throw new InvalidArgumentException('The --days option must be a positive integer.');
            }
            if ($rawLimit !== null && ($limit === false || $limit < 1)) {
                throw new InvalidArgumentException('The --limit option must be a positive integer.');
            }
            $result = $report->run(new CollectedOrderReportOptions($days, (string) $this->option('timezone'), $limit === false ? null : $limit));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->warn('DRY RUN: summarized tracezilla order creation remains disabled.');
        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Date', 'Currency', 'SKU', 'Quantity', 'Revenue'], array_map('array_values', $result->lines));
            $this->line(sprintf('Orders: %d returned, %d selected, %d skipped; lines skipped: %d.', $result->sourceOrders, $result->selectedOrders, $result->skippedOrders, $result->skippedLines));
        }

        return self::SUCCESS;
    }
}
