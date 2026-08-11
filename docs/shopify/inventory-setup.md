---
layout: default
nav_order: 40
parent: Shopify Setup
grand_parent: Setup
title: Inventory Setup
---

# Inventory setup

This guide prepares a Shopify development store for inventory synchronization
with tracezilla.

It supplies the Shopify prerequisites for the Laravel [Synchronize Inventory
recipe](../guides/sync-inventory.html).

The goal is to create a small and predictable test setup:

```text
tracezilla inventory
        ↓
matched by SKU
        ↓
Shopify inventory item
        ↓
selected Shopify location
```

During synchronization, tracezilla will be treated as the source of truth for
the available quantity. Development and testing should therefore use test
products in a Shopify development store—not production inventory.

## Before you begin

Make sure you already have:

- A Shopify development store.
- A custom app installed on that store.
- At least one active Shopify location.
- A few test products with unique SKUs.
- Matching SKUs available in tracezilla.

See [Products and locations](locations.html) if these are not ready yet.

## Understand Shopify inventory

Inventory belongs to a product **variant**, not directly to the product.
Shopify represents that relationship with an inventory item:

```text
Product
└── Variant
    ├── SKU
    └── Inventory item
        ├── Location A → Available: 25
        └── Location B → Available: 8
```

The connector needs three identifiers:

- The **SKU** for matching Shopify with tracezilla.
- The Shopify **inventory item ID** for identifying the stock item.
- The Shopify **location ID** for identifying where its inventory is stored.

You do not need to copy the inventory item IDs manually. The connector will
retrieve them through Shopify's GraphQL Admin API.

## Step 1: Choose a development location

1. Open the [Shopify Admin](https://admin.shopify.com).
2. Select the development store used for the integration project.
3. Select **Settings**.
4. Select **Locations**.
5. Open the location that should receive synchronized inventory.
6. Confirm that the location is active.
7. Write down its name so you can recognize it in the terminal.

Run the read-only location command:

```bash
docker compose exec app php artisan shopify:locations
```

### Why the location ID is needed

Shopify can store the same inventory item at several locations:

```text
TEST-COFFEE-250G
├── Development Warehouse → Available: 10
└── Retail Store          → Available: 3
```

The inventory item ID identifies **what** should be updated. The location ID
identifies **where** it should be updated. Shopify needs both IDs to address
one exact inventory level:

```text
Inventory item ID + Location ID = one inventory level
```

The connector will retrieve inventory item IDs automatically. You select the
location once and place its GraphQL ID in the inventory example so the code
cannot accidentally update another location.

Find the intended location by name in the command output and copy its GraphQL
ID. It has this form:

```text
gid://shopify/Location/123456789
```

It will later replace the example constant in
`SynchronizeInventory.php`:

```php
public const SHOPIFY_LOCATION_ID =
    'gid://shopify/Location/123456789';
```

The Shopify Admin page URL contains the same numeric part. For example:

```text
Admin URL:    .../settings/locations/123456789
GraphQL ID:   gid://shopify/Location/123456789
```

Use the ID printed by `docker compose exec app php artisan shopify:locations`
rather than constructing
it yourself. This verifies that the installed app can access the location and
provides the exact value expected by Shopify's GraphQL API.

Do not copy an ID from a production store. Location IDs belong to one specific
Shopify store and cannot be reused across stores.

<!-- Screenshot suggestion:
     Show the selected development location and its active status. -->

## Step 2: Enable inventory tracking

Repeat these steps for every test variant:

1. In Shopify Admin, select **Products**.
2. Open a test product.
3. If the product has variants, select the specific variant.
4. Find the **Inventory** section.
5. Check the inventory-tracking status:
   - If Shopify displays **Inventory not tracked**, select that control to
     enable inventory tracking.
   - If Shopify displays **Track quantity**, enable it.
   - If inventory quantities are already displayed, tracking is already
     enabled.
6. Confirm that the variant has a unique SKU.
7. Save the product.

For example, the inventory section might initially show:

```text
Inventory not tracked
1 location fulfils this: Banana Shop
SKU: BAN-020
```

The location assignment and SKU do not mean that Shopify is tracking the
quantity. Inventory synchronization requires tracking to be enabled as well.

Use recognizable test SKUs, for example:

```text
TEST-COFFEE-250G
TEST-COFFEE-1KG
```

The connector will not be able to match a variant that has no SKU. Duplicate
SKUs are ambiguous and should be corrected before synchronization.

<!-- Screenshot suggestion:
     Show the Inventory not tracked state, followed by the same inventory
     section after quantity tracking has been enabled. Include the SKU. -->

## Step 3: Stock the variant at the location

For each test variant:

1. Open its inventory settings.
2. Find the list of locations that stock the variant.
3. Make sure the selected development location stocks it.
4. Enter a small starting quantity, such as `3`.
5. Save the change.

Using a Shopify quantity that differs from tracezilla makes the later dry run
easy to verify. For example:

```text
Shopify available quantity:    3
tracezilla available quantity: 10
Expected dry-run result:       would update 3 → 10
```

When the customer's workflow uses more than one location, remember that
Shopify keeps a separate
quantity for each one. The connector must only update the configured location.

<!-- Screenshot suggestion:
     Show the variant stocked at the chosen location with a small quantity. -->

## Step 4: Decide how out-of-stock products behave

In the variant's inventory settings, decide whether customers may continue
buying when the available quantity reaches zero.

For a straightforward inventory test, leave **Continue selling when out of
stock** disabled. This makes a zero quantity unavailable for purchase.

This option controls selling behaviour. It does not prevent the connector from
reading or updating the quantity.

<!-- Screenshot suggestion:
     Show the Continue selling when out of stock option. -->

## Step 5: Grant API permissions

The inventory feature will use these Shopify access scopes:

| Scope | Purpose |
|:--|:--|
| `read_products` | Read variants and their SKUs |
| `read_locations` | Read the available Shopify locations |
| `read_inventory` | Read inventory items and quantities |
| `write_inventory` | Update quantities when execution is explicitly enabled |

Add the scopes to a new app version and release it. Then update or reinstall
the app on the development store if Shopify asks you to approve the new
permissions.

Update the local `.env` value:

```dotenv
SHOPIFY_SCOPE=read_products,read_locations,read_inventory,write_inventory
```

> `write_inventory` grants permission to change inventory. Having the
> permission does not cause a write by itself. The synchronization command
> will remain a dry run unless execution is explicitly enabled.

See [Authorize API](authorize-api.html) for the app-version and installation
steps.

<!-- Screenshot suggestion:
     Show the four selected API access scopes in the app version. -->

## Step 6: Record the expected test data

Before testing inventory synchronization, prepare a small comparison:

| SKU | Shopify quantity | tracezilla quantity | Expected result |
|:--|--:|--:|:--|
| `TEST-COFFEE-250G` | 3 | 10 | Would update |
| `TEST-COFFEE-1KG` | 5 | 5 | Unchanged |
| `TEST-TEA-GREEN` | Missing | 7 | Missing in Shopify |

This table gives you known results to compare with the command output.

## Readiness checklist

Before implementing or testing inventory synchronization, confirm that:

- You are using a Shopify development store.
- The chosen Shopify location is active.
- You have copied its GraphQL location ID.
- Every test variant has inventory tracking enabled.
- Every test variant has a unique SKU.
- Each variant is stocked at the chosen location.
- Shopify and tracezilla contain deliberately chosen test quantities.
- The app has all four required access scopes.
- The updated app version is installed on the development store.
- The project's `.env` requests the same scopes.

The Shopify store is now ready for an inventory integration that reads variants,
inventory item IDs, and quantities for the selected location. Any operation
that writes quantities should provide dry-run mode, explicit execution,
controlled limits, and production confirmation.
