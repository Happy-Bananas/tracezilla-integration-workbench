---
title: Project Scope
parent: Getting Started
layout: default
nav_order: 10
---

# Project scope

This project is a platform-neutral integration guide with practical examples
for Laravel, TypeScript, Python, and automation platforms. It is intended as
inspiration and a safe starting point—not as a universal, ready-to-deploy
connector.

## You own the business mapping

There is no single correct way to map two business systems. The developer
adapting these examples is responsible for deciding what makes sense for the
customer.

Examples of customer-specific decisions include:

- Which Shopify location corresponds to which tracezilla warehouse.
- Whether a Shopify variant represents a piece, box, case, or another unit.
- How tracezilla unit conversions become Shopify integer quantities.
- How names, tags, categories, weights, prices, VAT, and currencies map.
- Which system is the source of truth.
- Which records may be created or updated in production.

The project defaults demonstrate code paths. They are not business advice and
must be reviewed against the customer's products, workflows, and accounting
requirements.

## Use only what you need

You do not need to adopt the complete application or read every page.

Choose a task:

- [Synchronize Shopify variants to tracezilla SKUs](./guides/sync-catalog.html)
- [List and choose a Shopify location](./shopify/locations.html)
- [Prepare Shopify inventory](./shopify/inventory-setup.html)
- [Understand the architecture](./architecture/overview.html)

Each finished example aims to identify:

- The minimum files or snippets you need.
- Required credentials and API permissions.
- The customer-specific mapping point.
- Focused tests and commands.
- Safe preview and execution steps.

## Deployment boundary

These examples demonstrate API integration, mapping points, dry runs, and
controlled writes. The application that adopts them owns deployment,
scheduling, monitoring, recovery, and its operational policies.
