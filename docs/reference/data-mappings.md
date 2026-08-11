---
title: Data Mappings
parent: Reference
nav_order: 40
layout: default
---

# Data mappings

API fields describe available data; they do not decide what the customer's
data means. Record each mapping policy before enabling a write.

| Concept | Shopify | tracezilla | Required decision |
|:--|:--|:--|:--|
| SKU identity | Variant `sku` | SKU `sku_code` | Exact comparison, normalization, blanks, and duplicates |
| Product structure | Product and variants | SKU and its units | Which entity is created; names, units, weights, and conversion |
| Location | GraphQL location ID | Warehouse/location identifier | Explicit source-to-destination relationship |
| Inventory | Available quantity at one inventory level | Available quantities and unit conversions at a warehouse | Source of truth and conversion to a non-negative whole Shopify unit |
| Individual order identity | Stable order ID and name | Sales-order external reference | Prefix, uniqueness, duplicate check, and update policy |
| Order partner | Customer and shipping address | Customer, pickup, and delivery partners/locations | Partner resolution and address fallback |
| Order lines | SKU, current quantity, discounted price, currency | SKU code, quantity, unit price, currency | Discounts, taxes, shipping, refunds, exchange rate, and line grouping |
| Order state | Cancellation and fulfillment information | Order status and later transitions | Selection, lot handling, finishing, delivery, and invoicing |

Identifiers from different namespaces are not interchangeable. Keep Shopify
GraphQL IDs, Shopify legacy IDs, tracezilla database IDs, location numbers,
display names, and external references visibly labelled.

The platform-neutral workflow owns the expected behavior. Each implementation
must expose its demonstration mappings and tests so consultants can replace
them with customer-approved rules without changing authentication or transport
code.
