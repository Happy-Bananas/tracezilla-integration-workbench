<?php

namespace App\Console\Commands;

use App\Features\CatalogComparison\Actions\CompareCatalogs;
use App\Features\CatalogComparison\Options\CatalogComparisonOptions;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Compare Shopify variant SKUs with tracezilla SKU codes without writing.
 *
 * Usage: php artisan pull-catalog-from-shopify [--limit=10] [--json]
 */
class PullCatalogFromShopify extends Command
{
    protected $signature = 'pull-catalog-from-shopify
        {--limit= : Compare at most this many records from each catalog}
        {--json : Output the structured report as JSON}';

    protected $description = 'Compare Shopify and tracezilla SKU catalogs without writing';

    public function handle(CompareCatalogs $compare): int
    {
        try {
            $limit = $this->positiveLimit();
            $result = $compare->run(new CatalogComparisonOptions($limit));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('READ ONLY: no Shopify or tracezilla data was changed.');
            $this->line(sprintf('Shopify: %d, tracezilla: %d, shared: %d.', $result->shopifyCount, $result->tracezillaCount, count($result->presentInBoth)));
            $this->line('Only in Shopify: '.($result->onlyInShopify === [] ? 'none' : implode(', ', $result->onlyInShopify)));
            $this->line('Only in tracezilla: '.($result->onlyInTracezilla === [] ? 'none' : implode(', ', $result->onlyInTracezilla)));
            $this->line('Blank Shopify SKUs: '.count($result->blankShopifyVariantIds));
        }

        return self::SUCCESS;
    }

    private function positiveLimit(): ?int
    {
        $value = $this->option('limit');
        if ($value === null) {
            return null;
        }
        $limit = filter_var($value, FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1) {
            throw new InvalidArgumentException('The --limit option must be a positive integer.');
        }

        return $limit;
    }
}
