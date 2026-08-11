---
title: Command Reference
parent: Reference
grand_parent: Laravel
nav_order: 10
layout: default
---

# Artisan command reference

This page classifies every integration command currently found in
`app/Console/Commands`. A command appearing in `php artisan list` does not by
itself mean the workflow is maintained or safe to execute.

## Maintained commands

### `shopify:locations`

```bash
php artisan shopify:locations
php artisan shopify:locations --json
```

- **Direction:** Shopify → local output.
- **Writes:** none.
- **Purpose:** list locations, status, addresses, and GraphQL/legacy IDs.
- **Implementation:** maintained feature action and typed result objects.
- **Example:** [Validate Connections](../platforms/laravel-connection-validation.html#validate-shopify-locations).

### `tracezilla:skus-from-shopify`

```bash
php artisan tracezilla:skus-from-shopify --limit=10
php artisan tracezilla:skus-from-shopify --execute --limit=1
```

- **Direction:** Shopify → tracezilla.
- **Writes:** creates missing tracezilla SKUs only with `--execute`.
- **Default:** dry run.
- **Safety:** limit, duplicate checks, existing-SKU checks, structured results,
  and production confirmation.
- **Example:** [Create Missing tracezilla SKUs](../guides/sync-catalog.html).

### `shopify:inventory-from-tracezilla`

```bash
php artisan shopify:inventory-from-tracezilla --limit=10
php artisan shopify:inventory-from-tracezilla --execute --limit=1
```

- **Direction:** tracezilla → Shopify.
- **Writes:** sets an absolute Shopify available quantity with `--execute`.
- **Default:** dry run.
- **Safety:** limit, integer validation, source/destination quantity display,
  compare quantity, and production confirmation.
- **Example:** [Synchronize Inventory](../guides/sync-inventory.html).

### `tracezilla:orders-from-shopify`

```bash
php artisan tracezilla:orders-from-shopify --days=3 --limit=1
php artisan tracezilla:orders-from-shopify --execute --days=3 --limit=1
```

- **Direction:** Shopify → tracezilla.
- **Writes:** creates individual tracezilla sales orders with `--execute`.
- **Default:** dry run.
- **Safety:** date window, limit, external-reference duplicate check, mapping
  validation, and production confirmation.
- **Example:** [Import Individual Orders](../guides/import-individual-orders.html).

## Maintained read-only compatibility commands

These former legacy command names now use maintained feature actions, current
clients, positive limits, and structured output. They intentionally implement
the safe reporting stage of each workflow; the old automatic writes were not
carried forward.

| Command | Intended workflow | Current status |
|---|---|---|
| `pull-catalog-from-shopify` | Compare complete Shopify and tracezilla SKU catalogs | Read-only difference report; supports `--limit` and `--json` |
| `push-catalog-to-shopify` | Identify tracezilla SKUs requiring a Shopify product decision | Read-only report; product creation and price changes remain disabled |
| `pull-orders-from-shopify-collected` | Aggregate Shopify sales by business date, currency, and SKU | Dry-run report with explicit timezone, days, and limit options |

These commands remain read-only because this project does not define the
customer-specific identity, pricing, or reconciliation policies required for
their writes.

See [Review Missing Shopify
Products](../platforms/laravel-shopify-product-review.html) for the explicit
Shopify product-write boundary.

Order reporting has a dedicated guide for [Preview Collected
Orders](../platforms/laravel-collected-order-report.html).

## Safe command convention

Maintained synchronization commands follow this pattern:

```text
No --execute
    → read source and destination
    → validate and map
    → report what would change
    → perform no writes

--execute
    → authorize the documented writes
    → apply an optional small limit
    → require extra production confirmation
    → return structured item results
```

New workflow ports should use the same convention unless a stricter safety
model is required.
