<?php

namespace App\Features\ShopifyLocations\Actions;

use App\Clients\ShopifyClient;
use App\Features\ShopifyLocations\Data\ShopifyLocationData;
use App\Features\ShopifyLocations\Results\ShopifyLocationResult;
use App\GraphQL\Queries\GetLocations;
use UnexpectedValueException;

final readonly class ListShopifyLocations
{
    public function __construct(
        private ShopifyClient $client,
    ) {}

    public function run(): ShopifyLocationResult
    {
        $locations = [];
        $after = null;
        $seenCursors = [];

        do {
            $result = $this->client->graphql(
                GetLocations::QUERY,
                [
                    'first' => 250,
                    'after' => $after,
                ],
            );

            $connection = $result['data']['locations'] ?? null;

            if (! is_array($connection)
                || ! is_array($connection['nodes'] ?? null)
                || ! is_array($connection['pageInfo'] ?? null)
            ) {
                throw new UnexpectedValueException(
                    'Shopify locations response has an unexpected structure.'
                );
            }

            foreach ($connection['nodes'] as $location) {
                if (! is_array($location)) {
                    throw new UnexpectedValueException(
                        'Shopify location node has an unexpected structure.'
                    );
                }

                $locations[] = ShopifyLocationData::fromApiResponse($location);
            }

            $hasNextPage = $connection['pageInfo']['hasNextPage'] ?? null;
            $endCursor = $connection['pageInfo']['endCursor'] ?? null;

            if (! is_bool($hasNextPage)) {
                throw new UnexpectedValueException(
                    'Shopify locations page information is invalid.'
                );
            }

            if (! $hasNextPage) {
                break;
            }

            if (! is_string($endCursor)
                || trim($endCursor) === ''
                || isset($seenCursors[$endCursor])
            ) {
                throw new UnexpectedValueException(
                    'Shopify locations pagination cursor is invalid or repeated.'
                );
            }

            $seenCursors[$endCursor] = true;
            $after = $endCursor;
        } while (true);

        return new ShopifyLocationResult($locations);
    }
}
