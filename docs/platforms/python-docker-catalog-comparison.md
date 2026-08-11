---
title: Compare Catalogs
nav_order: 10
layout: default
parent: Examples
grand_parent: Python
---

# Python Docker catalog comparison

This read-only example compares both catalogs with the same result contract as
the TypeScript example, using Python 3.13 and only the standard library. It
is suitable for consultants who prefer Python or need a small dependency-free
starting point.

Docker Desktop, OrbStack, and other Docker Compose-compatible runtimes can run
it without installing Python locally.

## What it does

1. Exchanges Shopify client credentials for a short-lived access token.
2. Reads all Shopify product variants with GraphQL cursor pagination.
3. Reads all tracezilla SKUs using `links.next_page` pagination.
4. Compares exact, case-sensitive SKU codes.
5. Prints the same structured JSON shape as the TypeScript example.

It reports shared and missing SKUs, blank Shopify SKUs, and duplicate Shopify
SKUs. It contains no API write request.

## Configure the sandbox

From the project root:

```bash
cd examples/python-catalog-comparison
cp .env.example .env
```

Fill in the Shopify and tracezilla sandbox credentials in `.env`. The root
`.gitignore` excludes every file named `.env` anywhere in the repository, and
`.dockerignore` prevents it from entering the image build context.

## Run the mocked tests

```bash
docker compose run --rm --build test
```

The tests require no credentials or network API access. They verify token
exchange, both pagination mechanisms, GET-only tracezilla access, cross-host
pagination rejection, blank SKUs, and duplicates.

## Run the comparison

```bash
docker compose run --rm --build catalog-comparison
```

The output contract matches the TypeScript example, including `summary`,
`presentInBoth`, `onlyInShopify`, `onlyInTracezilla`, `blankShopifySkus`, and
`duplicateShopifySkus`.

## Files involved

```text
examples/python-catalog-comparison/
├── compose.yaml
├── src/cli.py
├── src/config.py
├── src/http.py
├── src/comparison.py
├── src/clients.py
└── tests/
```

## Safety limits

`CATALOG_PAGE_SIZE` defaults to 250 and is capped at 250.
`CATALOG_MAX_PAGES` defaults to 100. The clients reject repeated Shopify
cursors, repeated tracezilla pages, and tracezilla pagination links pointing
to another host. `HTTP_TIMEOUT_SECONDS` defaults to 30 seconds per request.

The Docker image and four mocked tests were verified with OrbStack on 4 August
2026. A live sandbox comparison remains an explicit validation task because
credentials are never copied into the image or repository.

Use [Data Mappings](../reference/data-mappings.html) to interpret the result or
compare this implementation with another platform.
