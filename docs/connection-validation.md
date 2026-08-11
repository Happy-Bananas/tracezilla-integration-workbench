---
title: Validate Connections
parent: Setup
nav_order: 30
layout: default
---

# Validate both API connections

Validate each API independently after completing the Shopify and tracezilla
setup guides. The connection checks confirm a narrow authenticated read;
recipe-specific scope validation happens when its read is run.

## Available implementation

The current browser validators and location command are provided by the
Laravel [Validate Connections recipe](./platforms/laravel-connection-validation.html).
Follow [Validate Connections with
Laravel](./platforms/laravel-connection-validation.html) for commands and
expected results.

Other platforms can implement the same small requests without adopting
Laravel.

## Ready to continue

Do not continue to a synchronization guide until:

- Shopify authentication succeeds.
- Shopify reports the same API version that the application requested.
- At least one expected Shopify location is returned.
- tracezilla authentication succeeds.
- The configured shop and tracezilla team are the intended sandbox accounts.

If a check fails, copy only the error message—not credentials—and use the
[Troubleshooting guide](./guides/troubleshooting.html).
