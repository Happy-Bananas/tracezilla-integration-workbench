---
title: Synchronize Inventory
parent: Examples
grand_parent: Laravel
nav_order: 70
layout: default
---

# Synchronize inventory

This cookbook treats one tracezilla
warehouse as the inventory source of truth and sets the available quantity at
one Shopify location.

```text
tracezilla warehouse inventory
        ↓ exact SKU match
customer-owned quantity mapper
        ↓
Shopify inventory at one location
```

The command is a dry run unless `--execute` is supplied.

From the project root, start the services before running any Docker command in
this guide:

```bash
docker compose up -d
docker compose ps
```

Continue only when the `app` service is running.

## Before you start

Complete the [Shopify inventory setup](../shopify/inventory-setup.html). Use
test accounts and test products while adapting the example.

Tracezilla must contain received stock, not only SKUs. Follow
[Add inventory to tracezilla](../tracezilla/create-inventory.html) to create a
purchase order, receive its lots, and verify the available quantity.

You must also select the Shopify location whose inventory this example may
update. Follow the [Shopify locations guide](../shopify/locations.html) to
configure the API permission and run:

```bash
docker compose exec app php artisan shopify:locations
```

Find the intended location in the output and copy its GraphQL ID:

```text
gid://shopify/Location/123456789
```

Open `SynchronizeInventory.php` and replace the two example constants with the
Shopify location ID and the Tracezilla warehouse number that belong together:

```php
public const SHOPIFY_LOCATION_ID =
    'gid://shopify/Location/123456789';

public const TRACEZILLA_WAREHOUSE_LOCATION_NUMBER = 2;
```

The Shopify location and tracezilla warehouse are not automatically related.
The adopting developer must confirm that this pairing represents the
customer's intended stock location.

## Review the quantity mapping

Open:

```text
app/Features/InventorySynchronization/Mappers/
└── TracezillaInventoryToShopifyQuantityMapper.php
```

The example converts traceable and non-traceable available quantities into
Shopify sellable units and adds them. This is only a demonstration policy.

The developer must decide, with the customer:

- Which tracezilla quantity represents sellable stock.
- How traceable and non-traceable stock should be combined.
- Whether unit conversions apply.
- How fractions, negative quantities, reservations, and bundles behave.

Do not execute against production until the mapper and its tests describe the
customer's rules. The example rejects negative or fractional Shopify
quantities instead of guessing how to round them.

## Run focused tests

```bash
docker compose exec app php artisan test \
  tests/Unit/Features/InventorySynchronization \
  tests/Feature/ShopifyInventoryServiceTest.php \
  tests/Feature/TracezillaInventoryServiceTest.php
```

The tests fake both APIs and do not modify either account.

## Preview one record

```bash
docker compose exec app php artisan shopify:inventory-from-tracezilla --limit=1
```

Example:

```text
DRY RUN: no Shopify inventory was changed.
[WOULD_UPDATE] BANANA-001: Would change quantity from 3 to 10.
Updated: 0, would update: 1, unchanged: 0, skipped: 0, failed: 0
```

Dry run still reads both systems. It does not call Shopify's inventory mutation.

## Execute one controlled update

After checking the SKU, warehouse, location, and mapped quantity:

```bash
docker compose exec app php artisan shopify:inventory-from-tracezilla --execute --limit=1
```

The write sets an **absolute** available quantity because tracezilla is the
source of truth in this example. It also sends Shopify's previously read
quantity as `compareQuantity`. Shopify rejects the write if that value changed
between reading and writing.

Production execution requires an additional terminal confirmation.

## Result behaviour

| Status | Meaning |
|:--|:--|
| `would_update` | Dry run found a different quantity |
| `updated` | Shopify accepted the absolute quantity |
| `unchanged` | Both systems already agree |
| `skipped` | SKU is missing, untracked, or not stocked at the location |
| `failed` | Mapping or writing could not be completed safely |

Matching is intentionally simple: exact SKU to exact SKU. Partner SKU codes,
bundles, multiple warehouses, and product-specific policies belong in a
customer adaptation.

## Copy the core action

```php
use App\Features\InventorySynchronization\Actions\SynchronizeInventory;
use App\Features\InventorySynchronization\Options\InventorySyncOptions;

$result = app(SynchronizeInventory::class)->run(
    new InventorySyncOptions(dryRun: true, limit: 10),
);
```

To authorize writes:

```php
$result = app(SynchronizeInventory::class)->run(
    new InventorySyncOptions(dryRun: false, limit: 1),
);
```

An HTTP controller, queue worker, or scheduler that uses the action directly
must provide its own authorization and production safeguards.

If Shopify reports that `inventoryLevel` requires `read_inventory`, follow
[Troubleshooting: Shopify denies an inventory field](troubleshooting.html#shopify-denies-an-inventory-field).

## Files used by this feature

```text
app/Clients/ShopifyClient.php
app/Clients/TracezillaClient.php
app/Console/Commands/UpdateInventoryInShopify.php
app/Features/InventorySynchronization/  ← location, warehouse, and mapping
app/GraphQL/Mutations/SetInventoryQuantity.php
app/GraphQL/Queries/GetInventoryItems.php
app/Services/ShopifyInventoryService.php
app/Services/TracezillaInventoryService.php
config/services.php
```

Copy the focused tests too: they are executable examples of the expected
mapping and API payloads.

Review [Data Mappings](../reference/data-mappings.html) before changing the
source of truth, location mapping, quantity policy, or write semantics.
