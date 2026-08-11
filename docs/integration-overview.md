---
title: Integration Overview
parent: Getting Started
nav_order: 20
layout: default
---

# Integration overview

Shopify and tracezilla hold related catalog, inventory, and order information,
but an integration must define how records correspond and which system owns
each value.

```text
Shopify API
    ↕
customer-specific rules and mapping
    ↕
tracezilla API
```

The setup guides establish safe API access. Each platform then provides
cookbook examples with the commands and checks required to obtain a result.
Shared API behavior lives in Reference.

Before implementing a write, agree on SKU identity, units, locations, source
of truth, order selection, and recovery with the customer. Start with a
read-only comparison or dry run.
