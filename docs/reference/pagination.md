---
title: Pagination
parent: Reference
nav_order: 30
layout: default
---

# Pagination

An integration must distinguish a complete result from one page of a result.
A successful first request is not evidence that the complete catalog, order
set, inventory set, or location list was read.

## Shopify GraphQL cursor pagination

Request a connection with `nodes` and:

```graphql
pageInfo {
  hasNextPage
  endCursor
}
```

Begin with a null cursor. When `hasNextPage` is true, send `endCursor` as the
next request's `after` value. Stop only when `hasNextPage` is false.

Fail instead of looping when Shopify says another page exists but returns an
empty cursor, or when the cursor repeats. Nested connections have independent
pagination. For example, fetching all orders does not automatically fetch more
than the requested line items for each order; the maintained individual-order
example skips orders whose line connection has another page.

## tracezilla next-page pagination

Request a bounded `perPage` value and read the response's
`links.next_page`. Continue with the query parameters from that link until it
is empty.

Treat an invalid or repeated next page as a failure. Coded examples should also
reject an absolute next-page URL for an unexpected host so authentication is
never forwarded outside the configured tracezilla origin.

## Limits and completeness

An execution or display limit is not a substitute for API pagination. Unless a
workflow explicitly defines a sampled report, read the complete source and
destination sets first, then apply the workflow limit to candidate records.
Report the inspected and selected counts so partial results remain visible.
