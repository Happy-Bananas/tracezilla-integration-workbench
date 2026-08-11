<?php

namespace App\GraphQL\Queries;

class GetLocations
{
    public const QUERY = <<<'GRAPHQL'
query GetLocations($first: Int!, $after: String) {
  locations(first: $first, after: $after) {
    nodes {
      id
      legacyResourceId
      name
      address {
        address1
        address2
        city
        province
        country
        zip
      }
      isActive
      hasActiveInventory
      fulfillsOnlineOrders
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
GRAPHQL;
}
