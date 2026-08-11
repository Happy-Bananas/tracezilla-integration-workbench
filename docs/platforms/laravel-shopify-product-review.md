---
title: Review Missing Shopify Products
parent: Examples
grand_parent: Laravel
nav_order: 50
layout: default
---

# Review missing Shopify products

The compatibility command reports tracezilla SKUs missing from Shopify.
Despite its historical name, it does not push anything to Shopify.

## Start the project

From the project root:

```bash
docker compose up -d
docker compose ps
```

Continue when the `app` service is running.

## Run a bounded report

```bash
docker compose exec app php artisan push-catalog-to-shopify --limit=10
```

The command prints:

```text
READ ONLY: product creation and price updates are intentionally disabled.
```

It then reports tracezilla-only SKUs as records that require a Shopify product
decision. It also reports Shopify-only SKUs for context.

For structured output:

```bash
docker compose exec app php artisan push-catalog-to-shopify --json
```

The JSON uses the same comparison result as [Compare Catalogs with
Laravel](laravel-catalog-comparison.html).

## Safety boundary

The command sends the Shopify token request and read-only GraphQL query plus
tracezilla `GET` requests. It contains no Shopify product mutation, price
update, or tracezilla write.

Do not replace this report with automatic product creation until the customer
decisions are implemented and tested.

## Focused test

```bash
docker compose exec app php artisan test \
  tests/Feature/Console/PushCatalogToShopifyCommandTest.php
```

## Files involved

```text
app/Console/Commands/PushCatalogToShopify.php
app/Features/CatalogComparison/
app/Services/ShopifyCatalogService.php
app/Services/TracezillaSkuService.php
tests/Feature/Console/PushCatalogToShopifyCommandTest.php
```

The test asserts that the reporting commands expose catalog differences and do
not send write requests.
