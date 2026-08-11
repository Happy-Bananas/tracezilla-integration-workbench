---
title: Create Missing tracezilla SKUs
parent: Examples
grand_parent: Laravel
nav_order: 60
layout: default
---

# Create missing tracezilla SKUs

This cookbook reads Shopify variants and previews or creates missing SKUs in
tracezilla.

It is designed for developers who want to run the example, understand the important pieces, and copy only the snippets or files they need.

## What This Feature Changes

The feature:

- Reads Shopify product variants.
- Reads existing tracezilla SKUs.
- Creates missing tracezilla SKUs only when execution is explicitly enabled.

It does not modify Shopify.

The default command is a dry run and does not modify tracezilla.

## Before You Start

Use a Shopify development store and a tracezilla demo account while developing.

You need:

- A running Laravel application.
- Shopify client credentials with product-read access.
- A tracezilla API key.
- At least one Shopify variant with an SKU.

From the project root, start the services:

```bash
docker compose up -d
docker compose ps
```

Continue when the `app` service is running.

## 1. Configure Credentials

Configure the variables in [Configuration
Variables](../reference/configuration.html), using only Shopify
`read_products` for this workflow. Follow [Authentication and
Secrets](../reference/authentication-and-secrets.html); the real `.env` must
remain outside Git.

If Laravel configuration has been cached, clear it:

```bash
docker compose exec app php artisan config:clear
```

## 2. Review the Example Mapping

Open:

```text
app/Features/CatalogSynchronization/Mappers/
└── ShopifyVariantToTracezillaSkuMapper.php
```

The demonstration mapping is:

| Shopify | tracezilla |
|---|---|
| Variant SKU | SKU code |
| Variant SKU | Global name |
| Example values in the mapper | Units, weights, and conversion |

The values `1.0`, `pcs`, and `colli` only make the example payload executable.
They are not universal defaults. Replace them with rules that match the
customer's products, weights, units, and conversions, then update the mapper
test.

Read [Mappers](../architecture/mappers.html) for the complete customization
guidance.

## 3. Run the Tests

Run the focused catalog tests:

```bash
docker compose exec app php artisan test \
  tests/Unit/Features/CatalogSynchronization \
  tests/Feature/ShopifyCatalogServiceTest.php \
  tests/Feature/TracezillaSkuServiceTest.php \
  tests/Feature/TracezillaSkusFromShopifyCommandTest.php
```

Run the complete project suite:

```bash
docker compose exec app php artisan test
```

Tests use fake HTTP responses and do not contact real Shopify or tracezilla accounts.

## 4. Preview a Small Dry Run

Start with ten variants:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --limit=10
```

Because `--execute` is absent, the command prints:

```text
DRY RUN: no tracezilla SKUs will be created.
```

A summary may look like:

```text
Shopify variants: 20 returned, 10 selected, 10 processed.
Created: 0, would create: 4, skipped: 5, invalid: 1, failed: 0
```

Review the JSON item results:

```json
{
  "source_id": "gid://shopify/ProductVariant/123",
  "sku": "BANANA-001",
  "status": "would_create",
  "reason": "dry_run",
  "message": "SKU would be created during execution.",
  "details": []
}
```

Common reasons include:

- `already_exists`
- `duplicate_shopify_sku`
- `missing_sku`
- `dry_run`
- `tracezilla_error`

## 5. Execute One Controlled SKU

After reviewing the dry run:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --execute --limit=1
```

This authorizes writes but processes at most one Shopify variant.

In the production Laravel environment, the command also asks for confirmation:

```text
This will create missing SKUs in production tracezilla. Continue?
```

The safe default answer is no.

Inspect the resulting SKU in tracezilla before increasing the limit.

## 6. Increase the Limit

Run another dry preview:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --limit=50
```

Then execute the reviewed selection:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --execute --limit=50
```

Only omit the limit after you understand the complete result:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --execute
```

## Copy the Core Action

Other Laravel code can reuse synchronization without invoking Artisan:

```php
use App\Features\CatalogSynchronization\Actions\SynchronizeCatalog;
use App\Features\CatalogSynchronization\Options\CatalogSyncOptions;

$result = app(SynchronizeCatalog::class)->run(
    CatalogSyncOptions::dryRun(limit: 10),
);

return $result->toArray();
```

To authorize execution:

```php
$result = app(SynchronizeCatalog::class)->run(
    CatalogSyncOptions::execute(limit: 1),
);
```

Any alternative interface must apply its own authorization before constructing execution options.

## Copy the Mapping Snippet

The central mapping operation is:

```php
$skuData = $skuMapper->map($shopifyVariant);
$response = $tracezillaSkuService->createSku($skuData);
```

Do not copy authentication headers or construct raw payload arrays in the command. Copy the client, data-object, mapper, and service boundaries that support the snippet.

## Files Used by This Feature

```text
app/Clients/
app/Features/CatalogSynchronization/
app/GraphQL/Queries/GetProductVariants.php
app/Services/ShopifyCatalogService.php
app/Services/TracezillaSkuService.php
app/Console/Commands/TracezillaSkusFromShopifyCommand.php
config/services.php
```

The feature also needs its corresponding tests and client credential entries
from `.env.example`.

## How Safety Is Enforced

```text
No --execute
    → CatalogSyncOptions::dryRun()
    → action records would_create
    → createSku() is never called

--execute
    → CatalogSyncOptions::execute()
    → action may call createSku()
    → only for valid missing SKUs
```

Existing tracezilla SKUs are retrieved across all API pages. The action also tracks duplicate Shopify SKUs within the current run.

## Troubleshooting

### Missing configuration

Example:

```text
Missing required client configuration [services.shopify.client_secret].
```

Add the missing `.env` value and run:

```bash
docker compose exec app php artisan config:clear
```

### Shopify GraphQL error

The client reports GraphQL errors even when Shopify returns HTTP 200. Review the query, API version, scopes, and error message.

### Invalid limit

Limits must be positive integers:

```bash
docker compose exec app php artisan tracezilla:skus-from-shopify --limit=0
```

is rejected before synchronization begins.

### Variant is invalid

`missing_sku` means a Shopify variant has no usable SKU. Add a unique SKU in Shopify and run another dry preview.

### tracezilla write fails

The item receives `failed` status and `tracezilla_error`. Review the HTTP status, tracezilla configuration, and destination-field requirements. The command exits with failure when an operational write fails.

## Next Customization

The easiest safe customization point is:

```text
ShopifyVariantToTracezillaSkuMapper
```

Change mapping rules there, update its focused tests, run a dry preview, and inspect the result before executing.

Review [Data Mappings](../reference/data-mappings.html) before changing the
selection, mapping, or write policy.
