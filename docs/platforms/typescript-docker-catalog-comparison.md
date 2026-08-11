---
title: Compare Catalogs
nav_order: 10
layout: default
parent: Examples
grand_parent: TypeScript
---

# TypeScript Docker catalog comparison

This read-only example compares both catalogs without depending on a visual
automation platform or application framework. It uses TypeScript, Node.js
built-in `fetch`, and Docker Compose.

You do not need to install Node.js, npm, or TypeScript. Docker Desktop,
OrbStack, and other Docker Compose-compatible runtimes use the same commands.

## What it does

The command:

1. Exchanges the Shopify client credentials for a short-lived access token.
2. Reads every Shopify product variant using GraphQL cursor pagination.
3. Reads every tracezilla SKU using `links.next_page` pagination.
4. Compares exact, case-sensitive SKU codes.
5. Prints structured JSON to standard output.

It reports SKUs present in both systems, SKUs found in only one system, blank
Shopify SKUs, and duplicate Shopify SKUs. It contains no product, SKU,
inventory, order, or price write operation.

## Prepare the configuration

From the project root:

```bash
cd examples/typescript-catalog-comparison
cp .env.example .env
```

Open `.env` and provide the sandbox values:

```dotenv
SHOPIFY_SHOP_URL=your-store.myshopify.com
SHOPIFY_CLIENT_ID=...
SHOPIFY_CLIENT_SECRET=...
SHOPIFY_SCOPE=read_products
SHOPIFY_API_VERSION=2025-10

TRACEZILLA_BASE_URL=https://app.tracezilla.com
TRACEZILLA_TEAM_SLUG=...
TRACEZILLA_API_KEY=...
```

The local `.env` file is excluded from Git and the Docker build context. Do
not commit it or include it in support requests.

## Run the mocked tests

Build the test image and run the tests before contacting either API:

```bash
docker compose run --rm --build test
```

The tests use mocked HTTP responses and require no credentials. They verify:

- Shopify token exchange and GraphQL cursor pagination.
- tracezilla next-page pagination using `GET` requests only.
- exact SKU comparison, including blank and duplicate Shopify SKUs.

## Run the comparison

```bash
docker compose run --rm --build catalog-comparison
```

The first build downloads the Node.js base image and three development
packages. Later runs use the Docker build cache unless the source or dependency
files change.

A successful result resembles:

```json
{
  "summary": {
    "shopifyVariants": 12,
    "tracezillaSkus": 15,
    "presentInBoth": 10,
    "onlyInShopify": 1,
    "onlyInTracezilla": 5,
    "blankShopifySkus": 1,
    "duplicateShopifySkus": 0
  },
  "presentInBoth": ["BANANA-001"],
  "onlyInShopify": ["SHOPIFY-ONLY"],
  "onlyInTracezilla": ["TRACEZILLA-ONLY"],
  "blankShopifySkus": [],
  "duplicateShopifySkus": []
}
```

The exact counts and codes depend on the sandbox data.

## Files involved

```text
examples/typescript-catalog-comparison/
├── compose.yaml
├── src/cli.ts
├── src/config.ts
├── src/http.ts
├── src/catalog/compare-catalogs.ts
├── src/clients/shopify-client.ts
├── src/clients/tracezilla-client.ts
└── tests/
```

## Safety limits

`CATALOG_PAGE_SIZE` defaults to `250`, the maximum used by the maintained
clients. `CATALOG_MAX_PAGES` defaults to `100` and prevents an unexpected API
pagination loop from running indefinitely. The clients also reject repeated
Shopify cursors, repeated tracezilla pages, and tracezilla next-page links that
switch to another host.

`HTTP_TIMEOUT_MS` defaults to 30 seconds per request. Errors mention the
service host and response status but never intentionally print credentials or
access tokens.

## Reuse in another framework

The clients and comparison function do not depend on Express, Next.js, NestJS,
or another web framework. A future serverless adapter should import them and
provide only configuration, invocation, logging, and result delivery. Keep the
comparison read-only until a separately reviewed write workflow is required.

Use [Data Mappings](../reference/data-mappings.html) to interpret the result or
compare this implementation with another platform.
