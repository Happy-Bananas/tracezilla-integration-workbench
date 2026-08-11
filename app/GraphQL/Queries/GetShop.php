<?php

namespace App\GraphQL\Queries;

class GetShop
{
    public const QUERY = <<<'GRAPHQL'
query {
  shop {
    id
    name
  }
}
GRAPHQL;
}