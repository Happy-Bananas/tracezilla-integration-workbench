---
title: Import Individual Orders
parent: Examples
grand_parent: Laravel
nav_order: 80
layout: default
---

# Import individual Shopify orders

This example reads recent Shopify orders and creates one tracezilla sales
order for each order:

```text
Shopify order
    ↓ order mapper owned by the adopting developer
tracezilla sales order
```

It is a dry run unless `--execute` is supplied.

## Before you start

1. Complete [Create the webshop partner](../tracezilla/webshop-partner.html).
   The example looks for a tracezilla customer named
   `Banana primary webshop`. It must have an owner and a primary location.
2. Complete [Add inventory to tracezilla](../tracezilla/create-inventory.html).
   Shopify and tracezilla must use the same exact SKU codes.
3. Grant the Shopify app `read_orders`, release the app version, and approve
   the update in the store. See [Authorize API](../shopify/authorize-api.html).
4. Add `read_orders` to the client scopes:

   ```dotenv
   SHOPIFY_SCOPE=read_products,read_locations,read_inventory,write_inventory,read_orders
   ```

The example only reads orders created during the requested number of days, so
it does not require access to older Shopify orders.

## Review the visible example choices

Open:

```text
app/Features/OrderSynchronization/Actions/SynchronizeOrders.php
```

Confirm these constants:

```php
public const TRACEZILLA_CUSTOMER_NAME = 'Banana primary webshop';
public const TRACEZILLA_WAREHOUSE_LOCATION_NUMBER = 2;
```

The first value identifies the webshop customer partner. The second identifies
the warehouse from which the sales order will be picked.

Then open:

```text
app/Features/OrderSynchronization/Mappers/
└── ShopifyOrderToTracezillaSalesOrderMapper.php
```

The mapper deliberately exposes these demonstration policies:

- The external reference is `SHP` plus Shopify's legacy order ID.
- Only DKK orders are accepted.
- The example exchange rate is `100`, following this tracezilla account's
  local-currency convention.
- Shopify lines with the same SKU are combined.
- Current quantities and Shopify's discounted unit prices are used.
- Empty SKU lines are ignored.
- Shopify's shipping address becomes the `deliver_to` location.
- The tracezilla order starts with status `from_edi`.
- No automatic lot selection, picking, dispatch, or delivery is requested.

These are not universal mappings. The developer must agree with the customer
on currency conversion, tax, discounts, refunds, shipping charges, payment
fees, delivery dates, order states, traceability, and address rules.

## Run the focused tests

```bash
docker compose exec app php artisan test tests/Unit/Features/OrderSynchronization
```

The tests use in-memory data and do not contact either account.

## Shopify order checklist

Before previewing an order, confirm:

- The order has a shipping address.

The example maps the Shopify shipping address to tracezilla's `deliver_to`
location. If the address is missing, the order fails.

## Preview one recent order

From the project root, start the containers:

```bash
docker compose up -d
```

Then preview one order:

```bash
docker compose exec app php artisan tracezilla:orders-from-shopify --limit=1
```

The default window is three days. Change it explicitly when needed:

```bash
docker compose exec app php artisan tracezilla:orders-from-shopify --days=7 --limit=1
```

Example output:

```text
DRY RUN: no tracezilla sales orders were created.
[WOULD_CREATE] #1001 (SHP123456): Would create one tracezilla sales order.

Created: 0, would create: 1, skipped: 0, failed: 0
```

Dry run still reads Shopify, resolves the tracezilla partner and warehouse,
and checks existing tracezilla references. It does not call the sales-order
write endpoint.

## Execute one controlled import

After reviewing the displayed order and its mapping:

```bash
docker compose exec app php artisan tracezilla:orders-from-shopify --execute --limit=1
```

The command checks the external reference first. If it already exists, the
order is skipped instead of intentionally updating it. Production execution
also requires terminal confirmation.

## Result behaviour

| Status | Meaning |
|:--|:--|
| `would_create` | Dry run found a new importable order |
| `created` | tracezilla accepted the new sales order |
| `skipped` | The order is cancelled, already exists, or exceeds the example's safe limits |
| `failed` | Required data could not be mapped or the write failed |

An order with more than 250 lines is skipped. Add nested line-item pagination
before adopting the example for stores where such orders are possible.

## Copy the core action

```php
use App\Features\OrderSynchronization\Actions\SynchronizeOrders;
use App\Features\OrderSynchronization\Options\OrderSyncOptions;

$result = app(SynchronizeOrders::class)->run(
    new OrderSyncOptions(dryRun: true, days: 3, limit: 1),
);
```

The action and mapper do not assume that these sample rules fit another
customer. Copy the focused tests with the feature, then change the mapper and
tests together.

## Files used by this feature

```text
app/Clients/ShopifyClient.php
app/Clients/TracezillaClient.php
app/Console/Commands/PullOrdersFromShopifyIndividual.php
app/Features/OrderSynchronization/
app/GraphQL/Queries/GetOrders.php
app/Services/ShopifyOrderService.php
app/Services/TracezillaSalesOrderService.php
```
