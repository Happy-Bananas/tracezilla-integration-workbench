<?php

namespace App\Services;

use App\Clients\ShopifyClient;
use App\Features\InventorySynchronization\Data\ShopifyInventoryItemData;
use App\GraphQL\Mutations\SetInventoryQuantity;
use App\GraphQL\Queries\GetInventoryItems;
use RuntimeException;

class ShopifyInventoryService
{
    public function __construct(private ShopifyClient $client) {}

    /** @return array<string, ShopifyInventoryItemData> */
    public function getInventoryBySku(string $locationId): array
    {
        $items = [];
        $after = null;

        do {
            $response = $this->client->graphql(GetInventoryItems::QUERY, [
                'first' => 250,
                'after' => $after,
                'locationId' => $locationId,
            ]);
            $connection = $response['data']['productVariants'];

            foreach ($connection['nodes'] as $node) {
                $item = ShopifyInventoryItemData::fromApiResponse($node);

                if ($item->sku !== null && $item->sku !== '') {
                    $items[$item->sku] = $item;
                }
            }

            $after = $connection['pageInfo']['endCursor'] ?? null;
        } while ($connection['pageInfo']['hasNextPage'] ?? false);

        return $items;
    }

    public function setAvailable(
        ShopifyInventoryItemData $item,
        int $quantity,
        string $locationId,
    ): void {
        $response = $this->client->graphql(SetInventoryQuantity::MUTATION, [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'referenceDocumentUri' => 'tracezilla://inventory-sync/'.$item->sku,
                'quantities' => [[
                    'inventoryItemId' => $item->inventoryItemId,
                    'locationId' => $locationId,
                    'quantity' => $quantity,
                    'compareQuantity' => $item->available,
                ]],
            ],
        ]);

        $errors = $response['data']['inventorySetQuantities']['userErrors'] ?? [];

        if ($errors !== []) {
            throw new RuntimeException(
                (string) ($errors[0]['message'] ?? 'Shopify rejected the inventory update.')
            );
        }
    }
}
