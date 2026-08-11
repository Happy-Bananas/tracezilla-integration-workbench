---
title: Validate Connections
parent: Install and Run
grand_parent: Laravel
nav_order: 10
layout: default
---

# Validate connections

The Laravel implementation provides browser-based connection checks for both
APIs and an Artisan command for Shopify locations. All three operations are
read-only.

## Before you start

1. [Install and run Laravel](../getting-started.html).
2. Complete the platform-neutral [Setup](../setup.html).
3. Add sandbox credentials to `.env`.

The application pages show whether required values are configured but do not
display client secrets, API keys, or access tokens.

## Start the project

From the project root, start the services defined in `compose.yaml`:

```bash
docker compose up -d
```

Confirm that the application and documentation containers are running:

```bash
docker compose ps
```

Clear Laravel's cached configuration inside the running application container:

```bash
docker compose exec app php artisan config:clear
```

The browser URLs below work only while the Docker Compose services are
running. `docker compose up -d` starts the Laravel application, documentation
site, and database in the background.

## Test Shopify

Open:

```text
http://localhost:8000/shopify
```

If the page does not open, run `docker compose ps` from the project root and
confirm that the `app` service is running and publishes port `8000`.

Run **Shopify Connection Test**. A successful result includes the shop name,
configured API version, Shopify's response API version, and a version match.

## Test tracezilla

Open:

```text
http://localhost:8000/tracezilla
```

This page is served by the same running `app` container.

Run **tracezilla Connection Test**. The implementation requests one SKU but
discards the payload. Success confirms the team slug and API key can perform
that read; it does not display SKU data as part of the connection result.

## Validate Shopify locations

Run:

```bash
docker compose exec app php artisan shopify:locations
```

For structured output:

```bash
docker compose exec app php artisan shopify:locations --json
```

The command follows every Shopify location page and reports names, statuses,
inventory and fulfilment flags, GraphQL IDs, legacy IDs, and addresses.

If PHP and its dependencies are installed directly on the computer, the
equivalent command is:

```bash
php artisan shopify:locations
```

## Focused tests

```bash
docker compose exec app php artisan test \
  tests/Feature/ShopifyTestControllerTest.php \
  tests/Feature/TracezillaTestControllerTest.php \
  tests/Feature/ShopifyLocationsCommandTest.php
```

## Files involved

```text
app/Http/Controllers/ShopifyTestController.php
app/Http/Controllers/TracezillaTestController.php
app/Console/Commands/CheckLocationsInShopify.php
app/Features/ShopifyLocations/
tests/Feature/ShopifyTestControllerTest.php
tests/Feature/TracezillaTestControllerTest.php
tests/Feature/ShopifyLocationsCommandTest.php
```

The tests fake all external requests and verify that credentials and API
payloads are not exposed by the connection results.
