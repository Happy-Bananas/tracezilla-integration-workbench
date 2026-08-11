---
title: Reference
parent: Make.com
nav_order: 20
layout: default
---

# Make.com reference

Make.com scenarios use modules connected by routes. HTTP modules call the two
APIs, mapping tokens pass values between modules, iterators turn arrays into
bundles, and aggregators collect bundles for comparison or output.

Keep credentials in Make.com connections or protected fields. Scenario exports,
module labels, notes, screenshots, and execution logs must not contain secrets.

The current example is a manually triggered, read-only catalog comparison. It
does not provide automatic scheduling, replay, complete operational monitoring,
or write recovery.

- [Authentication and Secrets](../reference/authentication-and-secrets.html)
- [Pagination](../reference/pagination.html)
- [Data Mappings](../reference/data-mappings.html)
