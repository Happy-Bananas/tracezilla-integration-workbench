---
title: Synchronization Results
parent: Reference
nav_order: 50
layout: default
---

# Synchronization results

A synchronization run can contain several outcomes at once. Return one
structured item result per selected record plus a run summary; do not reduce a
mixed run to a single success boolean.

## Common outcome vocabulary

| Outcome | Meaning |
|:--|:--|
| `would_create` / `would_update` | Dry run found a write candidate but made no write |
| `created` / `updated` | The destination accepted the intended write |
| `unchanged` | Source and destination already agree |
| `skipped` | A documented rule intentionally excluded the record |
| `invalid` | Source data or the mapped payload is unsafe |
| `failed` | An API or unexpected operational action failed |

Not every workflow needs every outcome. Stable reason codes should explain why
an item was skipped, invalid, or failed; a separate human-readable message can
provide context.

## Run summary and exit behavior

Include source, selected, processed, and per-outcome counts, plus whether the
run was a dry run and which limit applied. A command should exit unsuccessfully
when an operational request failed. Skipped or invalid records must remain
visible even if workflow policy permits the overall run to complete.

## Partial and uncertain failures

Process item results independently so one rejected record does not hide the
others. Never report a record as written until the destination accepts it.

A timeout or disconnected response can occur after a destination accepted a
write. Before retrying an uncertain create, read the destination using its
stable identity or external reference. For compare-and-set updates, perform a
fresh read and preview. Blind retries are unsafe unless the API and request use
a documented idempotency mechanism.

Error results may include a safe HTTP status or exception class. They must not
include tokens, authorization headers, full configuration, or uncontrolled
exception text.

The Laravel catalog-specific statuses and reason codes are documented in
[Architecture — Synchronization
Results](../architecture/synchronization-results.html).
