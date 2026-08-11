<?php

namespace App\GraphQL\Queries;

class GetProductVariants
{
    public const QUERY = <<<'GRAPHQL'
query GetProductVariants($first: Int!, $after: String) {
  productVariants(first: $first, after: $after) {
    nodes {
      id
      legacyResourceId
      sku
      price
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
GRAPHQL;
}