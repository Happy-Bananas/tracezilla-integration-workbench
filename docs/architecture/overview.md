---
title: Architecture Overview
parent: Reference
grand_parent: Laravel
nav_order: 40
layout: default
---

# Architecture Overview

This application demonstrates communication in both directions between two
external systems:

- **Shopify** can provide products, variants, locations, inventory, customers,
  and orders.
- **tracezilla** can provide SKUs, product information, prices, units,
  warehouses, inventory, lots, customers, and orders.

Neither system is automatically the source of truth for every kind of data.
The direction depends on the feature and the customer's business rules.

Examples in this project include:

```text
Shopify variants  → tracezilla SKUs
tracezilla SKUs   → Shopify products and prices
tracezilla stock  → Shopify inventory
Shopify orders    → tracezilla sales orders
```

Some workflows also read both systems before deciding whether anything needs
to change. For example, an inventory synchronization can read Shopify's current
quantity, read tracezilla's warehouse quantity, compare them, and only then
update Shopify.

The application sits between the systems. It reads data, applies an explicit
customer-specific mapping and synchronization policy, and optionally sends
data in either direction.

The architecture makes translation decisions visible; it does not decide what
the customer's data means. The developer adapting an example owns mappings
such as units, locations, names, prices, weights, taxes, direction of travel,
and the source of truth for each data type. Project defaults are examples that
must be reviewed, not universal rules.

You do not need to understand all of Laravel before working with this project. The most important idea is that different kinds of work belong in different layers.

## The Layers

The catalog synchronization uses the following flow:

```text
Artisan command
    ↓
Synchronization feature
    ↓
Services and mapper
    ↓
Typed data objects
    ↓
Shopify and tracezilla clients
    ↓
External APIs
```

Each layer has one main responsibility.

### Commands

An Artisan command is the interface used from the terminal. A command should:

- Read command-line options.
- Ask for confirmation when necessary.
- Start the synchronization.
- Display progress and results.
- Return a successful or failed exit code.

A command should not contain all the API and synchronization logic. Keeping commands small lets the same feature run later from a queue, controller, scheduler, or webhook.

### Synchronization Feature

The synchronization feature contains the business rules. It decides:

- Which Shopify variants are valid.
- Which tracezilla SKUs already exist.
- Which SKUs should be created or skipped.
- Whether the operation is a dry run.
- How successes and failures are reported.

This is the part another developer is most likely to customize.

### Services

Services perform operations for a specific area of an API.

For example:

- `ShopifyCatalogService` retrieves and paginates product variants.
- `TracezillaSkuService` lists and creates tracezilla SKUs.

Services know which endpoints or GraphQL queries to use, but they should not decide the complete synchronization policy.

### Mappers

A mapper translates one system's data into another system's format.

For example, the catalog mapper will decide how a Shopify variant becomes a tracezilla SKU payload. Keeping this rule in one class gives developers one obvious place to customize names, units, weights, and other defaults.

### Data Objects

External APIs return arrays of data. Typed data objects give those values meaningful names and PHP types.

For example, `ShopifyVariantData` represents one Shopify product variant. It defines its GraphQL ID, legacy ID, optional SKU, and price.

Read [Data Objects](./data-objects.html) for a beginner-friendly explanation.

### API Clients

Clients handle communication shared by all operations for one API. They are responsible for:

- Authentication.
- Base URLs.
- HTTP headers.
- Sending requests.
- Detecting HTTP and API errors.

The clients should not contain catalog-specific business rules.

## Why Use Layers?

Separating responsibilities provides several benefits:

- Each class is smaller and easier to understand.
- Tests can focus on one behavior at a time.
- API communication can be reused by multiple features.
- Business rules can change without rewriting authentication code.
- Another developer can copy one feature without copying the entire application.

## Catalog example

`ShopifyCatalogService` converts Shopify GraphQL nodes into
`ShopifyVariantData`. `ShopifyVariantToTracezillaSkuMapper` converts valid
variants into `TracezillaSkuData`, and `TracezillaSkuService` sends destination
payloads.

`CatalogSyncOptions` and `CatalogSyncResult` form the typed input and output of
`SynchronizeCatalog`. The action owns synchronization decisions, while the
Artisan command provides a thin, dry-run-by-default terminal interface.

The example paginates both APIs, enforces optional limits, identifies duplicate
and existing SKUs, and reports structured statuses and reason codes.
