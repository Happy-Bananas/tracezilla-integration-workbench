---
title: Preview Collected Orders
parent: Examples
grand_parent: Laravel
nav_order: 90
layout: default
---

# Preview collected orders

This recipe reads Shopify and prints a collected sales report; summarized
tracezilla order creation is disabled.

From the project root, start the containers:

```bash
docker compose up -d
```

Run a small report using the customer's business timezone:

```bash
docker compose exec app php artisan pull-orders-from-shopify-collected \
  --days=3 --timezone=Europe/Copenhagen --limit=10
```

Use JSON when another tool will consume the result:

```bash
docker compose exec app php artisan pull-orders-from-shopify-collected \
  --days=3 --timezone=Europe/Copenhagen --limit=10 --json
```

The report includes returned, selected, skipped-order, and skipped-line counts.
Cancelled orders and orders with more than the fetched line-item page are
skipped. Lines without an importable SKU and positive current quantity are
excluded.

Run its focused feature test:

```bash
docker compose exec app php artisan test \
  tests/Feature/Console/PullOrdersFromShopifyCollectedCommandTest.php
```

Implementation files:

```text
app/Console/Commands/PullOrdersFromShopifyCollected.php
app/Features/CollectedOrderReporting/
app/Services/ShopifyOrderService.php
tests/Feature/Console/PullOrdersFromShopifyCollectedCommandTest.php
```
