---
layout: default
nav_order: 30
parent: Shopify Setup
grand_parent: Setup
title: Products & Locations
---

# Products and locations

Before testing the integration, prepare a small product catalog and at
least one location in your Shopify development store.

A **location** is a place where inventory is stored, such as a warehouse,
retail store, or distribution centre. Shopify records the inventory for
each product variant separately at each location.

## Understand the relationship

A Shopify product describes the item you sell. A **variant** is a specific
version of that product, and the SKU identifies that version.

Inventory belongs to the variant's inventory item at a particular location:

```text
Product: T-shirt
└── Variant: Blue / Medium
    ├── SKU: TSHIRT-BLUE-M
    └── Inventory item
        ├── Happy Bananas Warehouse → Quantity: 25
        └── Happy Bananas Store     → Quantity: 8
```

The same SKU can therefore have different inventory quantities at different
locations.

## Open the location settings

1. Open the [Shopify Admin](https://admin.shopify.com).
2. Select the development store you use for this project.
3. Select **Settings** in the bottom-left corner.

4. Select **Locations**.

## Create or select a location

You can use an existing location or create a separate one for development.
A clear name such as `Development Warehouse` makes it easier to recognize
test data later.

To create one:

1. Select **Add location**.
2. Enter a descriptive name.
3. Enter the requested address information.
4. Make sure the location is active and can fulfil online orders when the
   customer's selected workflow uses it for order fulfilment.
5. Save the location.

> Use a development store and test locations while developing the connector.
> Do not experiment with inventory in a customer's production store.

## Create test products

Create a few simple products so the integration has safe data to retrieve:

1. In Shopify Admin, select **Products**.
2. Select **Add product**.
3. Enter a product title.
4. Add options such as size or colour when the test case needs multiple
   variants.
5. In the inventory section, enter a unique SKU for every variant.
6. Save the product.

Use recognizable test SKUs such as:

```text
TEST-COFFEE-250G
TEST-COFFEE-1KG
TEST-TEA-GREEN
```

Every variant selected for synchronization with tracezilla must have an SKU.

## Assign inventory to the location

For each product or variant:

1. Open the product in Shopify Admin.
2. Select the variant if the product has more than one.
3. Find the **Inventory** section.
4. Make sure inventory is stocked at the development location used by the
   integration test.
5. Enter a small test quantity.
6. Save the product.

Using different quantities can make later inventory tests easier to understand.
For example, assign `25` units to the warehouse and `8` units to the store.

## Readiness checklist

Before continuing, confirm that:

- You are working in a Shopify development store.
- At least one active location exists.
- You know which location the connector should use.
- At least one product exists.
- Every test variant has a unique SKU.
- Test inventory is assigned to the chosen location.

The Shopify test catalog is now ready to be retrieved by the connector.

## List locations from the terminal

The Laravel [Validate Connections
recipe](../platforms/laravel-connection-validation.html#validate-shopify-locations)
shows how to list and verify these locations.

The Shopify app must have the `read_locations` access scope. Add it to the
app version as explained in [Authorize API](authorize-api.html), then make
sure the local configuration requests both permissions:

```dotenv
SHOPIFY_SCOPE=read_products,read_locations
```

Run the read-only command:

```bash
php artisan shopify:locations
```

The command retrieves every page of locations and displays:

- The location name and active status.
- Whether it has active inventory.
- Whether it fulfils online orders.
- Its GraphQL and legacy numeric IDs.
- Its address.

To get structured output for a script or another application, use:

```bash
php artisan shopify:locations --json
```

This command only reads Shopify data. It does not create or modify locations,
products, or inventory.

See [Validate Connections with
Laravel](../platforms/laravel-connection-validation.html#validate-shopify-locations)
for the implementation and focused tests.

> If you add `read_locations` after installing the app, release the updated
> app version and update or reinstall the app as required so the store grants
> the new permission.
