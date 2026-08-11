---
title: Application Structure
parent: Reference
grand_parent: Laravel
nav_order: 20
layout: default
---

# Laravel application

This application contains focused examples for connecting Shopify and
tracezilla. You can run the examples in this repository or copy only the
relevant feature into another Laravel project.

## Main folders

```text
app/
├── Clients/       Shared authentication and HTTP configuration
├── Features/      Use-case code, typed data, mapping, and results
├── GraphQL/       Shopify GraphQL operations
├── Services/      Focused external API operations
└── Console/
    └── Commands/  Terminal interfaces
```

The maintained examples are organized under:

```text
app/Features/CatalogSynchronization/
├── Actions/
├── Data/
├── Mappers/
├── Options/
└── Results/
```

The Shopify locations example is organized under:

```text
app/Features/ShopifyLocations/
├── Actions/
├── Data/
└── Results/
```

Inventory and individual-order synchronization use the same feature structure
under `app/Features/InventorySynchronization` and
`app/Features/OrderSynchronization`.

## Run an example

Preview catalog synchronization without changing tracezilla:

```bash
php artisan tracezilla:skus-from-shopify --limit=10
```

List the Shopify locations available to the app:

```bash
php artisan shopify:locations
```

See [Synchronize Catalog](./guides/sync-catalog.html) for the standalone catalog
recipe and the exact customization points.

See [Command Reference](./laravel/command-reference.html) before running another
command. Some older command files remain as porting requirements and are not
maintained examples.

See [Automating Synchronization](./laravel/synchronization-automation.html) for
how Shopify webhooks, Laravel scheduling, cron, queues, and periodic
reconciliation fit together in a production integration.

## Copy an example

Start with the feature directory for the use case you need. Follow its guide
to identify the supporting client, service, GraphQL query, configuration, and
tests.

Do not copy every command by default. Customer-specific decisions belong in
the mapper or policy you create for that integration, including units,
locations, prices, weights, taxes, inventory rules, and sources of truth.
