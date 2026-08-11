<?php

namespace App\GraphQL\Mutations;

final class SetInventoryQuantity
{
    public const MUTATION = <<<'GRAPHQL'
mutation SetInventoryQuantity($input: InventorySetQuantitiesInput!) {
  inventorySetQuantities(input: $input) {
    inventoryAdjustmentGroup {
      changes {
        name
        delta
        quantityAfterChange
      }
    }
    userErrors {
      code
      field
      message
    }
  }
}
GRAPHQL;
}
