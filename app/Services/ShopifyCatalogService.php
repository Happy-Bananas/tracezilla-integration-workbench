<?php

namespace App\Services;

use App\Clients\ShopifyClient;
use App\Features\CatalogSynchronization\Data\ShopifyVariantData;
use App\GraphQL\Queries\GetProductVariants;

class ShopifyCatalogService
{
    public function __construct(
        protected ShopifyClient $client,
    ) {}

    /**
     * @return list<ShopifyVariantData>
     */
    public function getProductVariants(): array
    {
        $productVariants = [];
        $after = null;

        do {
            $result = $this->client->graphql(
                GetProductVariants::QUERY,
                [
                    'first' => 250,
                    'after' => $after,
                ]
            );

            $connection = $result['data']['productVariants'];

            foreach ($connection['nodes'] as $variant) {
                $productVariants[] = ShopifyVariantData::fromApiResponse($variant);
            }

            $after = $connection['pageInfo']['endCursor'];
        } while ($connection['pageInfo']['hasNextPage']);

        return $productVariants;
    }

    public function getVariantSkuMapping(): array
    {
        $mapping = [];

        foreach ($this->getProductVariants() as $variant) {
            if (! $variant->hasSku()) {
                continue;
            }

            $mapping[$variant->sku] = [
                'variant_id' => $variant->legacyId,
                'price' => $variant->price,
            ];
        }

        return $mapping;
    }
}
