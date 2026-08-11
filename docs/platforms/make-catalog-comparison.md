---
title: Compare Catalogs
nav_order: 10
layout: default
parent: Examples
grand_parent: Make.com
---

# Make.com read-only catalog comparison

This read-only scenario proves that both APIs can be
reached and exposes a small set of Shopify variants and tracezilla SKUs for inspection. It never
creates or changes products, SKUs, stock, or orders.

The guide assumes no previous Make experience. In Make, a **scenario** is the
whole workflow, a **module** is one step, a **bundle** is one item moving
between steps, and **mapping** means using output from an earlier module as an
input to a later one.

## What the finished scenario does

1. Request a short-lived Shopify access token.
2. Read up to 250 Shopify product variants with GraphQL.
3. Read tracezilla SKUs.
4. Compare the two lists by exact, case-sensitive SKU code.
5. Produce a report; do not write to either service.

Keep scheduling **off** while building and testing. Use **Run once** only after
all credentials have been supplied and every API operation has been checked as
read-only.

## Before you begin

Complete the Shopify and tracezilla authorization guides first. The Shopify app
needs only the scopes required to read products. You also need:

- The store hostname, for example `your-store.myshopify.com`.
- The Shopify client ID and client secret.
- The tracezilla team slug and API key.

Do not paste secrets into module names, notes, screenshots, exported
blueprints, or this repository. Prefer Make's credential facilities when a
suitable credential type is available. Restrict access to the scenario and
rotate any secret accidentally exposed in an export.

## Create the scenario

In Make, choose **Scenarios**, create a new scenario, and name it:

```text
Shopify to tracezilla — Read-only catalog comparison
```

Leave the schedule switch off.

## Module 1: obtain a Shopify token

Add **HTTP > Make a request** and configure it as follows:

| Setting | Value |
| --- | --- |
| Authentication type | No authentication |
| URL | `https://YOUR_STORE.myshopify.com/admin/oauth/access_token` |
| Method | `POST` |
| Body content type | `application/x-www-form-urlencoded` |
| Parse response | Yes |

Add these body fields:

| Name | Value |
| --- | --- |
| `grant_type` | `client_credentials` |
| `client_id` | Your Shopify client ID |
| `client_secret` | Your Shopify client secret |

Do not put the credentials in query parameters. Save the module, but do not run
the scenario yet.

> A shell of this module has been created in the project sandbox. It contains
> the store token URL and `grant_type`, but intentionally contains no client ID
> or client secret.

## Module 2: read Shopify variants

Add another **HTTP > Make a request** after module 1:

| Setting | Value |
| --- | --- |
| URL | `https://YOUR_STORE.myshopify.com/admin/api/2025-10/graphql.json` |
| Method | `POST` |
| Body content type | `application/json` |
| Parse response | Yes |

Add the header `X-Shopify-Access-Token` and map its value to module 1's
`access_token` output; do not copy a temporary access token into the module.
Make adds the JSON content type from the selected body content type, so a
separate `Content-Type` header is optional.

Use this request body:

```json
{
  "query": "query CatalogForComparison { productVariants(first: 250) { nodes { id sku title product { title } } } }"
}
```

This one-page query is intentionally bounded. Variants with an empty SKU must
be reported as data-quality issues and must not be matched by title or product
ID.

## Module 3: read tracezilla SKUs

Add a third **HTTP > Make a request**:

| Setting | Value |
| --- | --- |
| URL | `https://app.tracezilla.com/api/v1/YOUR_TEAM_SLUG/skus` |
| Method | `GET` |
| Parse response | Yes |

Add the header `Authorization: Bearer YOUR_TRACEZILLA_API_KEY` and these query
parameters:

| Name | Value |
| --- | --- |
| `sortBy` | `sku_code` |
| `sortDirection` | `asc` |
| `perPage` | `250` |

Start with one page. Before using this recipe on a larger catalog, add and test
pagination against the response metadata instead of silently comparing only
the first page.

## First safe run

Before selecting **Run once**, verify all of the following:

- The schedule is off.
- Every request is `GET` or a read-only Shopify GraphQL query; the token request
  is the only other `POST`.
- No module calls a product, inventory, order, or SKU mutation endpoint.
- Shopify has read-only product scopes.
- Both result limits are one page of at most 250 records.

Run once. Open the numbered bubble above each module to inspect its input and
output bundles. Expected results are an `access_token`, Shopify variant nodes,
and a tracezilla SKU collection. HTTP `401` or `403` means authorization or
scope is incomplete; do not broaden permissions until the failing request is
identified.

The complete four-module sandbox scenario was verified on 4 August 2026 with
the schedule disabled. Both API reads returned 20 SKUs, from `BAN-001` through
`BAN-020`. The comparison reported `MATCH`, with empty `only_in_shopify` and
`only_in_tracezilla` arrays.

## Module 4: build the comparison report

Make discovers fields from real output, so add this step after the three HTTP
modules have run successfully. Add **Tools > Set multiple variables**, keep the
variable lifetime at `One cycle`, and add these variables:

| Variable name | Value |
| --- | --- |
| `shopify_skus` | `map(3.data.data.productVariants.nodes; "sku")` |
| `tracezilla_skus` | `map(4.data.data; "sku_code")` |
| `comparison_status` | `if(length(arrayDiff(map(3.data.data.productVariants.nodes; "sku"); map(4.data.data; "sku_code"))) = 0; if(length(arrayDiff(map(4.data.data; "sku_code"); map(3.data.data.productVariants.nodes; "sku"))) = 0; "MATCH"; "DIFFERENCES"); "DIFFERENCES")` |
| `only_in_shopify` | `arrayDiff(map(3.data.data.productVariants.nodes; "sku"); map(4.data.data; "sku_code"))` |
| `only_in_tracezilla` | `arrayDiff(map(4.data.data; "sku_code"); map(3.data.data.productVariants.nodes; "sku"))` |
| `present_in_both` | `arrayIntersect(map(3.data.data.productVariants.nodes; "sku"); map(4.data.data; "sku_code"))` |

The numbers in Make's mapping tokens depend on the module numbers in your
scenario. Select the fields from Make's mapping panel instead of typing the
numbers blindly. The comparison uses exact SKU code as its only identity rule
and produces these groups:

- present in both systems;
- present only in Shopify;
- present only in tracezilla.

These correspond to the canonical `presentInBoth`, `onlyInShopify`, and
`onlyInTracezilla` result groups. This Make recipe uses snake-case variable
names.

Open the numbered bubble above the Tools module after **Run once**. A matching
catalog shows `comparison_status: MATCH`, empty difference arrays, and the
shared SKUs under `present_in_both`. Keep the output inside the scenario
inspector for this version. Email, webhooks, spreadsheets, and all write
modules are intentionally outside this guide.

> If tracezilla returns `401 Unauthorized`, reopen its HTTP module and inspect
> the complete `Authorization` value. Make's mapping helper can append the
> Shopify access-token token to text already in the field. The value must
> contain only `Bearer ` followed by the tracezilla API key.

## Known limitations

This is a consultant learning example, not a production synchronization. Each
catalog read is limited to one page of 250 records; do not interpret `MATCH` as
a complete comparison if either catalog is larger. It also does not implement
automatic duplicate-SKU reporting, retries, rate-limit handling, durable run history, alerting, or secret
rotation. A successful read proves connectivity and mapping; it does not prove
that a catalog write would be safe.

The formulas compare the raw values exposed by Make. Inspect and correct
leading or trailing SKU whitespace before interpreting a result; the coded
examples trim it at their API boundaries.

Use [Data Mappings](../reference/data-mappings.html) to review the matching
rules before extending the scenario.
