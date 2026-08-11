---
title: Examples
parent: Laravel
nav_order: 10
has_children: true
layout: default
---

# Laravel examples

Choose the task you want to run. Complete [Install and
Run](./getting-started.html) and [Setup](./setup.html) first.

| Example | Main project files | Command |
|:--|:--|:--|
| [Compare Catalogs](./platforms/laravel-catalog-comparison.html) | `app/Features/CatalogComparison/` | `php artisan pull-catalog-from-shopify` |
| [Review Missing Shopify Products](./platforms/laravel-shopify-product-review.html) | `app/Features/CatalogComparison/` | `php artisan push-catalog-to-shopify --limit=10` |
| [Create Missing tracezilla SKUs](./guides/sync-catalog.html) | `app/Features/CatalogSynchronization/` | `php artisan tracezilla:skus-from-shopify --limit=10` |
| [Synchronize Inventory](./guides/sync-inventory.html) | `app/Features/InventorySynchronization/` | `php artisan shopify:inventory-from-tracezilla --limit=1` |
| [Import Individual Orders](./guides/import-individual-orders.html) | `app/Features/OrderSynchronization/` | `php artisan tracezilla:orders-from-shopify --limit=1` |
| [Preview Collected Orders](./platforms/laravel-collected-order-report.html) | `app/Features/CollectedOrderReporting/` | `php artisan pull-orders-from-shopify-collected --limit=10` |

Run commands in the container by prefixing them with
`docker compose exec app`. Each example page contains the complete command,
files involved, and verification steps.
