---
title: Shopify Setup
parent: Setup
nav_order: 10
layout: default
has_children: true
---

# Shopify setup

Create a safe Shopify development environment, prepare predictable test data,
and authorize the minimum Admin API access needed by the integration.

No prior Shopify development experience is required. The guides are written as
text-first procedures because Shopify may change dashboard layouts and labels
without changing the underlying setup outcome.

## Setup path

Complete these guides in order:

1. [Create or join a Partner account](./shopify/shopify-partner-account.html).
2. [Create a development store](./shopify/shopify-development-store.html).
3. [Create products and locations](./shopify/locations.html).
4. [Prepare inventory tracking](./shopify/inventory-setup.html) when the
   selected workflow uses the inventory synchronization example.
5. [Create, release, and install an app](./shopify/authorize-api.html).
6. [Validate the Shopify connection](./connection-validation.html).

## Expected result

At the end of Shopify setup, the project should have:

- A Shopify Partner organization.
- A development store with a permanent `myshopify.com` domain.
- At least one location and one variant with a unique test SKU.
- An installed app with the minimum required scopes.
- A client ID and client secret stored outside version control.
- A successful read-only connection check.

Use development data throughout this section. Do not test inventory or order
writes in a customer's production store.
