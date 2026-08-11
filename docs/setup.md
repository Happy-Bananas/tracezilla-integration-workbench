---
title: Setup
nav_order: 20
layout: default
has_children: true
---

# Setup

Set up safe test environments in Shopify and tracezilla, authorize the APIs,
and verify both connections before running a synchronization example.

This section is platform-neutral: complete it whether the implementation uses
Laravel, TypeScript, Python, an automation platform, or another integration
stack.

## Recommended path

1. [Set up Shopify](./shopify-configuration.html): create a Partner account,
   development store, test catalog, location, and installed app.
2. [Set up tracezilla](./tracezilla.html): create a demo account, test business
   data, and an API token.
3. [Validate both connections](./connection-validation.html) with the Laravel
   reference application's read-only checks.

Do not use production accounts while learning or adapting an example. Store
credentials in environment variables or a platform secret store, never in
source code or documentation.
