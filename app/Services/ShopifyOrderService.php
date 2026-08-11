<?php

namespace App\Services;

use App\Clients\ShopifyClient;
use App\Features\OrderSynchronization\Data\ShopifyOrderData;
use App\GraphQL\Queries\GetOrders;
use DateTimeImmutable;

class ShopifyOrderService
{
    public function __construct(private ShopifyClient $client) {}

    /** @return list<ShopifyOrderData> */
    public function getOrdersCreatedSince(DateTimeImmutable $createdSince): array
    {
        $orders = [];
        $after = null;
        $filter = "created_at:>='{$createdSince->format(DATE_ATOM)}'";

        do {
            $response = $this->client->graphql(GetOrders::QUERY, [
                'first' => 100,
                'after' => $after,
                'query' => $filter,
            ]);
            $connection = $response['data']['orders'] ?? [];

            foreach ($connection['nodes'] ?? [] as $order) {
                $orders[] = ShopifyOrderData::fromApiResponse($order);
            }

            $after = $connection['pageInfo']['endCursor'] ?? null;
        } while ((bool) ($connection['pageInfo']['hasNextPage'] ?? false));

        return $orders;
    }
}
