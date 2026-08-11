---
title: Synchronization Options
parent: Reference
grand_parent: Laravel
nav_order: 80
layout: default
---

# Synchronization Options

`CatalogSyncOptions` describes how one catalog synchronization is allowed to run.

```text
app/Features/CatalogSynchronization/Options/CatalogSyncOptions.php
```

It currently represents two decisions:

- Is this a dry run or an authorized execution?
- Is the number of processed variants limited?

## Why Use an Options Object?

The terminal command accepts flags such as:

```bash
php artisan tracezilla:skus-from-shopify --execute --limit=1
```

The command is only one possible interface. The same synchronization may later run from:

- An Artisan command.
- A queue job.
- A scheduler.
- A controller.
- A webhook.

If synchronization logic reads terminal flags directly, it can only be reused from the terminal. Instead, the interface translates its inputs into one options object:

```text
Command flags
    ↓
CatalogSyncOptions
    ↓
SynchronizeCatalog action
```

The action receives the same typed object regardless of where the request originated.

## Safe Defaults

Creating the object without arguments produces a dry run with no limit:

```php
$options = new CatalogSyncOptions();
```

Its values are:

```php
$options->dryRun; // true
$options->limit;  // null
```

Dry run is the default because accidentally omitting an execution instruction must not write production data.

`null` means no processing limit has been selected. During early production verification, an explicit limit is safer.

## Named Constructors

The class provides named constructors that communicate intent more clearly than raw Boolean values.

### Dry Run

Preview at most ten variants:

```php
$options = CatalogSyncOptions::dryRun(limit: 10);
```

### Execution

Authorize processing with a limit of one:

```php
$options = CatalogSyncOptions::execute(limit: 1);
```

This reads more clearly than:

```php
new CatalogSyncOptions(false, 1);
```

A reader should not need to remember what `false` means.

## Checking Write Authorization

The synchronization action can ask:

```php
if ($options->willExecute()) {
    // A write may be performed after all other checks pass.
}
```

or inspect:

```php
if ($options->dryRun) {
    // Record what would be created without sending POST /skus.
}
```

`willExecute()` does not perform a write. It only reports the authorization represented by the immutable options object.

## Limit Validation

The limit may be:

- `null`, meaning no limit.
- A positive integer such as `1`, `10`, or `250`.

Zero and negative limits are rejected:

```text
Catalog synchronization limit must be a positive integer.
```

The synchronization action enforces the limit as the maximum number of Shopify
variants selected for processing during that run.

## Why `readonly` Matters

The options class is `readonly`. Once a synchronization starts, another part of the application cannot silently change a dry run into an execution:

```php
$options->dryRun = false; // PHP rejects this.
```

The action receives one stable safety decision for the complete run.

## Options Do Not Enforce Safety Alone

`CatalogSyncOptions` represents intent. It does not contact Shopify or tracezilla and cannot prevent writes by itself.

`SynchronizeCatalog` enforces the contract:

```text
Dry run
    → read, map, validate, compare, and report
    → never call TracezillaSkuService::createSku()

Execution
    → perform the same checks
    → call createSku() only for approved missing SKUs
```

Tests for the action verify that a dry run sends no tracezilla write requests.

The command converts `--execute` and `--limit` into this object. Running the command without `--execute` is now a dry run and sends no tracezilla SKU write request.

## Testing

The unit tests verify:

- Safe defaults.
- Explicit limited dry runs.
- Explicit limited executions.
- Rejection of zero and negative limits.

Run them with:

```bash
php artisan test tests/Unit/Features/CatalogSynchronization/CatalogSyncOptionsTest.php
```

The object is plain PHP and does not require Laravel services, HTTP clients, or external accounts.

## Reusing the Options Contract

The standalone file can be copied with the catalog synchronization feature:

```text
app/Features/CatalogSynchronization/Options/CatalogSyncOptions.php
```

Applications may extend the concept later with additional explicit decisions, such as a location filter or failure policy. Avoid adding API credentials or environment access to this object; credentials remain client configuration.
