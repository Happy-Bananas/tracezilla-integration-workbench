---
layout: default
title: Architecture
parent: Reference
grand_parent: Laravel
nav_order: 30
---

# Architecture

The examples separate API communication, external data, customer-specific
mapping, synchronization policy, and user interfaces so each responsibility
can be understood and adapted independently.

The detailed pages appear alongside this overview under Laravel so the public
navigation remains compact. Start with the part relevant to your task:

- [Overview](./architecture/overview.html) — responsibilities and data flow.
- [Clients](./architecture/clients.html) — authentication and HTTP behaviour.
- [Data Objects](./architecture/data-objects.html) — validated API-boundary data.
- [Mappers](./architecture/mappers.html) — customer-specific transformation.
- [Synchronization Options](./architecture/synchronization-options.html) —
  dry-run and execution controls.
- [Synchronization Results](./architecture/synchronization-results.html) —
  structured outcomes and reason codes.
- [Synchronization Flow](./architecture/sync-flow.html) — the complete catalog
  example from command to APIs.
