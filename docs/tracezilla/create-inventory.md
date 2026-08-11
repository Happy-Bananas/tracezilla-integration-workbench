---
layout: default
nav_order: 30
parent: tracezilla Configuration
grand_parent: Setup
title: Add Inventory to tracezilla
---

# Add inventory to tracezilla

This guide supplies test stock for the Laravel [Synchronize Inventory
recipe](../guides/sync-inventory.html). Creating an SKU is not
enough: tracezilla needs received stock before its inventory API returns a
quantity.

This guide creates test inventory through a purchase order:

```text
Partner
  ↓ purchase order
SKU and quantity
  ↓ Receive all
Available tracezilla inventory
```

Use a tracezilla demo account and test SKUs. The adopting developer is
responsible for adapting the purchasing workflow to the customer's real
process.

## Before you start

You need:

- The [webshop partner](webshop-partner.html).
- A SKU that exists in both tracezilla and Shopify.
- A tracezilla location that can receive the stock.
- **Inventory tracked** enabled for the matching Shopify variant.

The SKU values must match exactly. For example:

```text
tracezilla SKU: BAN-002
Shopify SKU:    BAN-002
```

## Create a missing test SKU from Shopify

If the matching test SKU does not exist in tracezilla yet, the Laravel catalog
example can create it from a Shopify variant. Shopify and tracezilla API access
must already be configured and validated.

First preview a small selection:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --limit=10
```

The command is a dry run unless `--execute` is supplied. Review the reported
SKU codes, then create at most one missing test SKU:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --execute --limit=1
```

Inspect the created SKU in tracezilla before continuing. The example mapper
uses demonstration values for units, weights, and conversion. Review those
values before using the command with customer data.

See [Create Missing tracezilla SKUs](../guides/sync-catalog.html) for the mapping,
validation rules, result statuses, and complete controlled-execution procedure.

> Before running the Laravel synchronization, Shopify must track inventory for
> the matching variant at the configured location. Otherwise, the command
> intentionally skips the SKU instead of changing Shopify.

## Create a purchase order

1. Sign in to tracezilla.
2. Open **Purchase**.
3. Open **Purchase Orders**.
4. Select **Create Purchase Order**.
5. Under **Order Details**, select the partner created in the previous guide.
6. Open **Delivery Details**.
7. Set **Pickup date** to today.
8. Select **Save**.

<!-- Screenshot suggestion:
     Show Create Purchase Order, the selected partner, and today's Pickup
     date under Delivery Details. -->

## Add the order line

After saving the purchase order:

1. Select **Create Lots**.
2. Select the test SKU, for example `BAN-002`.
3. Enter a small test quantity.
4. Save the lot or order line.

For the running example:

```text
SKU:      BAN-002
Quantity: 400
```

The purchase order is now open, but the quantity is not yet available
inventory.

<!-- Screenshot suggestion:
     Show the Create Lots form with BAN-002 and quantity 400. -->

## Receive the inventory

In the upper-right corner of the open purchase order:

1. Open the order actions.
2. Select **Receive all**.
3. Confirm that all test lots were received.

Receiving the lots moves the quantity into tracezilla inventory.

<!-- Screenshot suggestion:
     Show the Receive all action and the received order status. -->

## Verify in tracezilla

Open **Warehouse**, then **Inventory**. Find the test SKU and confirm that the
available quantity is present.

For example:

```text
BAN-002 → 400 available
```

The inventory API may update after the web interface. If the Laravel dry run
still processes zero records immediately after receiving the lot, wait and
retry before creating duplicate stock.

Also confirm that `TRACEZILLA_WAREHOUSE_LOCATION_NUMBER` in
`SynchronizeInventory.php` identifies the location where the lot was received.

## Prepare the matching Shopify variant

In Shopify Admin, open the product variant with the same SKU:

1. Confirm that its SKU is exactly `BAN-002`.
2. Enable **Inventory tracked**.
3. Select **Edit locations** if necessary.
4. Make sure the location configured as `SHOPIFY_LOCATION_ID` stocks it.
5. Save the product.

Without these settings, the Laravel command intentionally reports:

```text
[SKIPPED] BAN-002: Shopify does not track this item at the configured location.
```

## Preview the synchronization

Run:

```bash
docker compose exec app php artisan shopify:inventory-from-tracezilla
```

The dry run should now report either:

```text
[WOULD_UPDATE] BAN-002: Would change quantity from 0 to 400.
```

or, if Shopify already contains 400:

```text
[UNCHANGED] BAN-002: Quantity is already 400.
```

No Shopify inventory is changed during the dry run.

## Execute a controlled update

After reviewing the dry-run quantity:

```bash
docker compose exec app php artisan shopify:inventory-from-tracezilla --execute --limit=1
```

The command sets Shopify's available quantity to the mapped tracezilla
quantity. Read the Laravel [Synchronize
Inventory](../guides/sync-inventory.html) recipe before adapting or executing
this example against production data.

## Checklist

- The purchase order has a partner.
- Pickup date is set.
- The order contains a lot with the intended SKU and quantity.
- **Receive all** has been completed.
- The inventory appears under tracezilla **Warehouse → Inventory**.
- The configured tracezilla warehouse is the location that received the lot.
- The Shopify variant has the exact same SKU.
- Shopify inventory tracking is enabled at the configured location.
