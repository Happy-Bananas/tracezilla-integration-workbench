---
title: Synchronization Results
parent: Reference
grand_parent: Laravel
nav_order: 90
layout: default
---

# Synchronization Results

The platform-neutral outcome and recovery contract is [Synchronization
Results](../reference/synchronization-results.html). This page documents the
Laravel catalog implementation in detail.

Catalog synchronization does not return an undocumented collection of arrays. It returns a `CatalogSyncResult` containing one structured `CatalogSyncItemResult` for every selected Shopify variant.

```text
CatalogSyncResult
└── CatalogSyncItemResult[]
```

## Why Structure the Result?

A synchronization can have several outcomes in the same run:

- One SKU is created.
- One would be created during a dry run.
- One already exists.
- One Shopify variant has no SKU.
- One tracezilla request fails.

A single `true` or `false` cannot explain all those outcomes. Structured results let commands, queue jobs, controllers, logs, and tests use the same facts.

## Item Statuses

`CatalogSyncStatus` is a PHP backed enum. An enum defines a limited set of allowed values.

| Status | Meaning |
|---|---|
| `created` | The SKU was created in tracezilla |
| `would_create` | Dry run determined that the SKU is eligible for creation |
| `skipped` | No write was needed or allowed for this item |
| `invalid` | Source data or mapped data cannot be synchronized |
| `failed` | An attempted external operation failed |

Code uses the enum rather than manually typing status strings:

```php
CatalogSyncStatus::WouldCreate;
```

Serialization uses its stable string value:

```json
{
  "status": "would_create"
}
```

## Reason Codes

Status describes the broad outcome. Reason explains why.

| Reason | Typical status | Meaning |
|---|---|---|
| `created` | `created` | tracezilla accepted the SKU |
| `dry_run` | `would_create` | A write was deliberately suppressed |
| `already_exists` | `skipped` | SKU was found in tracezilla |
| `duplicate_shopify_sku` | `skipped` | An earlier Shopify variant in the same run used the SKU |
| `missing_sku` | `invalid` | Shopify variant has no usable SKU |
| `invalid_payload` | `invalid` | Mapping or destination validation failed |
| `tracezilla_error` | `failed` | tracezilla or an unexpected write operation failed |

Stable reason codes are easier for tests and other applications to consume than changing human-readable sentences.

## One Item Result

Each `CatalogSyncItemResult` contains:

```php
new CatalogSyncItemResult(
    sourceId: 'gid://shopify/ProductVariant/123',
    sku: 'BANANA-001',
    status: CatalogSyncStatus::Skipped,
    reason: CatalogSyncReason::AlreadyExists,
    message: 'SKU already exists in tracezilla.',
);
```

`sourceId` identifies the Shopify variant even when its SKU is missing.

`message` is written for humans. `status` and `reason` are the stable machine-readable values.

## Run Summary

`CatalogSyncResult::summary()` provides counts and run context:

```php
[
    'source_count' => 20,
    'existing_sku_count' => 100,
    'selected_count' => 10,
    'processed_count' => 10,
    'created_count' => 0,
    'would_create_count' => 4,
    'skipped_count' => 5,
    'invalid_count' => 1,
    'failed_count' => 0,
    'dry_run' => true,
    'limit' => 10,
]
```

The counts distinguish:

- Variants returned by Shopify.
- Variants selected after applying the limit.
- Items actually classified by the synchronization.

## Failure and Exit Codes

The command asks:

```php
$result->hasFailures();
```

Operational failures cause a failed command exit code. Skipped and invalid source records remain visible in the report but do not currently make the complete command fail.

This policy can be changed later without changing the meaning of the item statuses.

## Safe Error Details

Expected HTTP failures record the status code without copying authorization headers or complete exception text.

Unexpected failures use a safe message and record only the exception class:

```php
[
    'message' => 'An unexpected error occurred while creating the tracezilla SKU.',
    'details' => [
        'exception' => RuntimeException::class,
    ],
]
```

This prevents internal exception messages from accidentally exposing confidential values in terminal JSON.

## Testing

Run the result tests:

```bash
php artisan test tests/Unit/Features/CatalogSynchronization/CatalogSyncResultTest.php
```

The action and command tests verify that real synchronization decisions produce the correct statuses, reasons, counts, and exit codes.
