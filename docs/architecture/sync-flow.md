---
title: Synchronization Flow
parent: Reference
grand_parent: Laravel
nav_order: 100
layout: default
---

# Sync Flow

The catalog synchronization is coordinated by:

```text
app/Features/CatalogSynchronization/Actions/SynchronizeCatalog.php
```

The action receives typed options and returns a structured result:

```text
CatalogSyncOptions
    ↓
SynchronizeCatalog::run()
    ↓
CatalogSyncResult
```

## Complete Flow

```text
1. Fetch all Shopify variants
2. Fetch all existing tracezilla SKU codes
3. Apply the optional processing limit
4. Classify every selected Shopify variant
5. Map valid missing variants to TracezillaSkuData
6. Dry run: record would_create
   Execution: POST missing SKU to tracezilla
7. Return one structured result
```

## Decision Flow for One Variant

```text
Does the Shopify variant have an SKU?
├── No
│   └── invalid: missing_sku
└── Yes
    ├── Already exists in tracezilla
    │   └── skipped: already_exists
    ├── Already seen in this Shopify run
    │   └── skipped: duplicate_shopify_sku
    └── New SKU
        ├── Mapping fails
        │   └── invalid: invalid_payload
        ├── Dry run
        │   └── would_create: dry_run
        └── Execution
            ├── API succeeds
            │   └── created: created
            └── API fails
                └── failed: tracezilla_error
```

## Limits

The action fetches the complete Shopify catalog so it can report the source count, then selects at most the requested number of variants:

```php
$selectedVariants = $options->limit === null
    ? $variants
    : array_slice($variants, 0, $options->limit);
```

With `--limit=1`, at most one Shopify variant is processed and at most one tracezilla SKU can be created.

## Idempotency

An idempotent synchronization can run repeatedly without creating the same SKU repeatedly.

The action protects this in two places:

1. It retrieves all existing tracezilla SKU codes before processing.
2. It tracks Shopify SKUs already encountered in the current run.

After a successful creation, the new SKU is also added to the in-memory existing set.

`TracezillaSkuService::getSkuCodes()` follows `links.next_page`, so existing-SKU checks are not limited to the first API page.

## Dry-Run Enforcement

The action checks dry-run options before calling the write service:

```php
if ($options->dryRun) {
    // Record would_create and continue.
}
```

`TracezillaSkuService::createSku()` is only called in the execution branch.

Unit and feature tests verify that dry runs never call or send `POST /skus`.

## Responsibilities

The action owns synchronization decisions. It does not:

- Read command-line flags.
- Display terminal output.
- Authenticate API requests.
- Construct GraphQL documents.
- Construct raw tracezilla payload arrays.

Those responsibilities remain in options, the command, clients, queries, data objects, mappers, and services.

This separation allows another interface to reuse the action:

```php
$result = app(SynchronizeCatalog::class)->run(
    CatalogSyncOptions::dryRun(limit: 10),
);
```

Read [Synchronize Catalog](../guides/sync-catalog.html) for the end-to-end quick start.
