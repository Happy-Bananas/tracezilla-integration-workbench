<?php

namespace App\Console\Commands;

use App\Features\CatalogComparison\Actions\CompareCatalogs;
use App\Features\CatalogComparison\Options\CatalogComparisonOptions;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Report tracezilla SKUs that require a Shopify product decision.
 *
 * This compatibility command is read-only; product creation and price updates
 * remain disabled until the customer-specific mapping policies are approved.
 *
 * Usage: php artisan push-catalog-to-shopify [--limit=10] [--json]
 */
class PushCatalogToShopify extends Command
{
    protected $signature = 'push-catalog-to-shopify
        {--limit= : Inspect at most this many records from each catalog}
        {--json : Output the structured report as JSON}';

    protected $description = 'Report tracezilla SKUs missing from Shopify without writing';

    public function handle(CompareCatalogs $compare): int
    {
        try {
            $value = $this->option('limit');
            $limit = $value === null ? null : filter_var($value, FILTER_VALIDATE_INT);
            if ($value !== null && ($limit === false || $limit < 1)) {
                throw new InvalidArgumentException('The --limit option must be a positive integer.');
            }
            $result = $compare->run(new CatalogComparisonOptions($limit === false ? null : $limit));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->warn('READ ONLY: product creation and price updates are intentionally disabled.');
        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line('Would require a Shopify product decision: '.($result->onlyInTracezilla === [] ? 'none' : implode(', ', $result->onlyInTracezilla)));
            $this->line('Shopify-only SKUs: '.($result->onlyInShopify === [] ? 'none' : implode(', ', $result->onlyInShopify)));
        }

        return self::SUCCESS;
    }
}
