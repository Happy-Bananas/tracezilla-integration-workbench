---
title: Troubleshooting
nav_order: 80
layout: default
---

# Troubleshooting

Start with the exact error printed by the command. The examples below explain
the cause, the smallest useful check, and the corrective action.

## Connection-test diagnosis

Use the narrowest failing request before investigating a larger workflow.

| Symptom | Check |
|---|---|
| Shopify token request returns `401` | Confirm the permanent shop hostname, client ID, client secret, and installed app |
| Shopify query returns `403` or an access error | Confirm the active app version declares the required scope and the store approved it |
| Shopify reports a different API version | Stop and review the configured version before running another workflow |
| tracezilla returns `401` | Confirm the complete API key and `Bearer` authorization value |
| tracezilla returns `404` | Confirm the base URL and team slug belong together |
| Connection succeeds but a recipe fails | Test the recipe-specific read and permission; connection success is intentionally narrow |

Never paste credentials into an error report. The Laravel connection pages
show configuration status without displaying secret values. See
[Authentication and Secrets](../reference/authentication-and-secrets.html) and
[Configuration Variables](../reference/configuration.html) for the shared
rules and complete variable list.

## Shopify location validation returns no locations

First confirm that at least one active location exists in the intended Shopify
development store. Then confirm:

1. The active app version declares `read_locations`.
2. The installed app has been updated and has approved that version.
3. `.env` requests `read_locations`.
4. Laravel configuration was cleared after changing `.env`.

Run the read-only command again:

```bash
php artisan shopify:locations
```

An empty successful response is different from an authorization error. It
means the request completed but no locations were visible; do not select an ID
or run inventory synchronization until the expected location appears.

## Shopify denies an inventory field

Example:

```text
Shopify GraphQL request failed: Access denied for inventoryLevel field.
Required access: `read_inventory` access scope.
```

This means Shopify authentication succeeded, but the app installation on the
store has not granted the required inventory permission.

There are two separate permission states:

1. The active app release declares the required scopes.
2. The installed app on the store has approved those scopes.

Creating and releasing a new app version does not by itself update the
permissions of an older store installation.

### Fix the installed app

1. In the Shopify Dev Dashboard, open the active app version.
2. Confirm that its scopes include:

   ```text
   read_products
   read_locations
   read_inventory
   write_inventory
   ```

3. Open [Shopify Admin](https://admin.shopify.com).
4. Select the development store.
5. Open **Settings**, then **Apps and sales channels**.
6. Select the integration app.
7. Select **Update**.
8. Review and approve the additional permissions.

Retry the dry run:

```bash
docker compose exec app php artisan shopify:inventory-from-tracezilla --limit=1
```

No Laravel restart should be needed. The example requests a new Shopify access
token each time the command runs.

## A new app release is not active in the store

Symptoms can include an app that still behaves like the previous release,
missing scopes after releasing a new version, or an authorization error even
though the development dashboard shows the expected configuration.

A release updates the app definition, but the installed app may still need to
be refreshed and its new permissions approved by the store.

1. Open [Shopify Admin](https://admin.shopify.com) and select the target store.
2. Open **Settings**, then **Apps and sales channels**.
3. Open the integration app.
4. Select **Update** or the available permission-review action, if shown.
5. Review and approve the permissions.
6. Reload the app page.
7. Retry the read-only connection check or dry run.

If Shopify shows no update action, confirm that the correct app version was
released, the app is installed on the intended store, and the requested scope
in `.env` matches the released version. Clear Laravel's cached configuration
after changing `.env`.

### Why changing `.env` is not enough

This client setting states which scopes the application requests:

```dotenv
SHOPIFY_SCOPE=read_products,read_locations,read_inventory,write_inventory,read_orders
```

It cannot grant permissions. Shopify grants them only after the scopes are
declared in an active app release and approved for the store installation.

## Laravel still uses an old client setting

If `.env` was changed but Laravel reports an old value, clear cached
configuration:

```bash
php artisan config:clear
```

Restarting the application is normally unnecessary for an Artisan command.

## A required client setting is missing

Example:

```text
Missing required client configuration [services.shopify.client_id].
```

Compare `.env` with `.env.example`, then provide the missing authentication or
connection value. Example-specific mappings, locations, warehouse numbers,
tags, and units intentionally live in their feature code rather than `.env`.

Never paste API secrets into command output, documentation, screenshots, or
committed files.

## Catalog SKU is skipped or invalid

Recipe: [Create Missing tracezilla SKUs](./sync-catalog.html).

Use the result reason before changing data:

| Reason | Corrective action |
|---|---|
| `already_exists` | Inspect the existing tracezilla SKU; do not create another |
| `duplicate_shopify_sku` | Resolve the duplicate Shopify variants before execution |
| `missing_sku` | Add a unique SKU to the Shopify variant |
| `invalid_payload` | Correct the customer-owned units, weights, name, or conversion mapping |
| `tracezilla_error` | Review the safe HTTP status and verify whether creation succeeded before retrying |

The demonstration mapper's `pcs`, `colli`, weight, and conversion values are
not universal defaults. A technically valid payload may still be wrong for the
customer.

## A tracezilla SKU write has an uncertain result

Do not retry a failed create request blindly. A connection can fail after
tracezilla accepted the request but before the application received the
response.

1. Run [Compare Catalogs](../platforms/laravel-catalog-comparison.html) again.
2. Search for the exact SKU in tracezilla.
3. If it exists, inspect its mapped fields rather than creating it again.
4. Retry only when the read confirms that the SKU was not created.

The current example does not send an idempotency key for tracezilla SKU
creation.

## Shopify product creation is intentionally disabled

The `push-catalog-to-shopify` command is a read-only compatibility report. Its
name does not mean it creates products or updates prices.

Read [Review Missing Shopify
Products](../platforms/laravel-shopify-product-review.html) for the customer
decisions required before implementing a Shopify write.

## Inventory dry run processes zero records

Recipe: [Synchronize Inventory](./sync-inventory.html).

Example:

```text
Updated: 0, would update: 0, unchanged: 0, skipped: 0, failed: 0
```

Check that `TRACEZILLA_WAREHOUSE_LOCATION_NUMBER` in
`SynchronizeInventory.php` is the location where the lot was actually
received. A supplier location may also be marked as a warehouse, so do not
assume that the company location contains the lot.

Tracezilla's aggregated inventory API may update after the lot appears in the
web interface. If the warehouse mapping is correct, wait and retry the dry run
before changing code or creating duplicate stock.

## Shopify item is skipped as untracked

Example:

```text
[SKIPPED] BAN-001: Shopify does not track this item at the configured location.
```

Confirm that the matching Shopify variant:

- Has inventory tracking enabled.
- Is stocked at `SHOPIFY_LOCATION_ID`.
- Uses the exact same SKU as Tracezilla.

See [Shopify inventory setup](../shopify/inventory-setup.html) for the Shopify
Admin steps.

## An individual order is skipped or fails

Recipe: [Import Individual Orders](./import-individual-orders.html).

| Result or message | Corrective action |
|:--|:--|
| Existing external reference | Inspect the existing tracezilla order; the example is create-only and will not update it |
| Cancelled Shopify order | Confirm that cancelled orders should remain excluded |
| More than 250 lines | Implement Shopify line-item pagination before importing that order |
| No shipping address | Agree on a fallback address policy or correct the source order |
| No importable SKU lines | Add valid SKUs and positive current quantities, or define a customer-specific non-SKU policy |
| Unsupported currency | Replace the example's DKK-only mapping with the agreed currency and exchange-rate policy |

If execution reports a network or server failure, the outcome may be
uncertain. Search tracezilla for the displayed `SHP…` external reference before
retrying. A retry is safe only after confirming that the order does not exist.

## A collected-order date is unexpected

The collected-order report groups by the `--timezone` business date, not
necessarily by the UTC date shown in Shopify data. Run the report again with
the customer's IANA timezone, for example `Europe/Copenhagen`, and compare the
JSON output. Summarized tracezilla order creation remains disabled.
