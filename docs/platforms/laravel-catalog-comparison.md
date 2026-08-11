---
title: Compare Catalogs
parent: Examples
grand_parent: Laravel
nav_order: 40
layout: default
---

# Compare catalogs

The Laravel command reads every available page
from both APIs, compares unique SKU codes, and never sends a catalog write.

## Before you start

1. [Install and run Laravel](../getting-started.html).
2. Complete the platform-neutral [Setup](../setup.html).
3. Add Shopify and tracezilla sandbox credentials to `.env`.
4. Clear cached configuration:

   ```bash
   php artisan config:clear
   ```

When PHP runs in Docker, replace `php artisan` with:

```bash
docker compose exec app php artisan
```

## Run a bounded comparison

```bash
php artisan pull-catalog-from-shopify --limit=10
```

The command prints a `READ ONLY` message, catalog counts, and the difference
groups. `--limit=10` limits the records included from each catalog after the
API pages have been read. It helps inspect the output but does not prove that
complete catalogs match.

## Get structured JSON

```bash
php artisan pull-catalog-from-shopify --json
```

| Laravel JSON | Canonical meaning |
|---|---|
| `present_in_both` | `presentInBoth` |
| `only_in_shopify` | `onlyInShopify` |
| `only_in_tracezilla` | `onlyInTracezilla` |
| `blank_shopify_variant_ids` | Variants excluded because their SKU is blank |

The `status` is `match` when both difference arrays are empty. Review blank
variant IDs separately. The current Laravel report collapses repeated SKU
codes but does not list duplicates separately; use the TypeScript or Python
report when automatic duplicate diagnostics are required.

## Run the focused test

```bash
docker compose exec app php artisan test \
  tests/Feature/Console/PullCatalogFromShopifyCommandTest.php
```

The test fakes both APIs, checks the report, and asserts that no catalog write
request was sent.

## Relevant files

```text
app/Console/Commands/PullCatalogFromShopify.php
app/Features/CatalogComparison/Actions/CompareCatalogs.php
app/Features/CatalogComparison/Results/CatalogComparisonResult.php
tests/Feature/Console/PullCatalogFromShopifyCommandTest.php
```

Review [Data Mappings](../reference/data-mappings.html) before changing its
matching policy, and update the focused tests with the new semantics.
