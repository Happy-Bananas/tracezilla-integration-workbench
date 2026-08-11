---
title: Configuration Variables
parent: Reference
nav_order: 20
layout: default
---

# Configuration variables

The connection and credential names below are shared by the Laravel,
TypeScript, and Python examples. Make.com stores equivalent values in scenario
connections and module fields rather than an `.env` file. Timeout variable
names are implementation-specific and listed separately.

## Shopify

| Variable | Required | Meaning | Example |
|:--|:--|:--|:--|
| `SHOPIFY_SHOP_URL` | Yes | Permanent store hostname, without an Admin path | `example.myshopify.com` |
| `SHOPIFY_CLIENT_ID` | Yes | App client ID | secret value |
| `SHOPIFY_CLIENT_SECRET` | Yes | App client secret | secret value |
| `SHOPIFY_SCOPE` | Yes | Comma-separated scopes required by the selected workflow | `read_products,read_locations` |
| `SHOPIFY_API_VERSION` | Yes | Explicit Admin API version reviewed by the integration | `2025-10` |

`SHOPIFY_SCOPE` requests permissions during authentication; it cannot grant
permissions absent from the released and approved app installation.

## tracezilla

| Variable | Required | Meaning | Example |
|:--|:--|:--|:--|
| `TRACEZILLA_BASE_URL` | Yes | Application base URL, without a team API path | `https://app.tracezilla.com` |
| `TRACEZILLA_TEAM_SLUG` | Yes | Team identifier used in API paths | `demo-team` |
| `TRACEZILLA_API_KEY` | Yes | Bearer API token | secret value |

## Implementation-specific timeouts

| Implementation | Variables | Defaults |
|:--|:--|:--|
| Laravel | `SHOPIFY_TIMEOUT`, `TRACEZILLA_TIMEOUT`; `SHOPIFY_CONNECT_TIMEOUT`, `TRACEZILLA_CONNECT_TIMEOUT` | 30-second request, 10-second connection |
| TypeScript | `HTTP_TIMEOUT_MS` | 30,000 milliseconds |
| Python | `HTTP_TIMEOUT_SECONDS` | 30 seconds |

## Laravel configuration cache

Laravel reads these values through `config/services.php`. After changing
`.env`, clear cached configuration inside the running container:

```bash
docker compose exec app php artisan config:clear
```

Warehouse numbers, Shopify location IDs, partner names, external-reference
prefixes, units, and conversion rules are workflow mappings—not client
configuration. Keep them visible beside the feature that owns the decision.

Follow [Authentication and Secrets](./authentication-and-secrets.html) when
storing or sharing any value.
