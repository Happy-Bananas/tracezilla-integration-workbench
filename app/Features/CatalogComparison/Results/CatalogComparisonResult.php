<?php

namespace App\Features\CatalogComparison\Results;

final readonly class CatalogComparisonResult
{
    /**
     * @param  list<string>  $presentInBoth
     * @param  list<string>  $onlyInShopify
     * @param  list<string>  $onlyInTracezilla
     * @param  list<string>  $blankShopifyVariantIds
     */
    public function __construct(
        public array $presentInBoth,
        public array $onlyInShopify,
        public array $onlyInTracezilla,
        public array $blankShopifyVariantIds,
        public int $shopifyCount,
        public int $tracezillaCount,
        public ?int $limit,
    ) {}

    public function matches(): bool
    {
        return $this->onlyInShopify === [] && $this->onlyInTracezilla === [];
    }

    public function toArray(): array
    {
        return [
            'status' => $this->matches() ? 'match' : 'differences',
            'shopify_count' => $this->shopifyCount,
            'tracezilla_count' => $this->tracezillaCount,
            'limit' => $this->limit,
            'present_in_both' => $this->presentInBoth,
            'only_in_shopify' => $this->onlyInShopify,
            'only_in_tracezilla' => $this->onlyInTracezilla,
            'blank_shopify_variant_ids' => $this->blankShopifyVariantIds,
        ];
    }
}
