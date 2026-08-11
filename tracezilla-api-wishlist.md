# tracezilla API Wishlist

## Note to the CTO

This wishlist comes from building and documenting the Shopify–tracezilla
reference integration. The requests are intended to make integrations easier
to validate, operate, and support without reading or exposing customer business
data.

## 1. Authenticated API metadata endpoint

**Highest priority**

Provide a lightweight endpoint such as:

```http
GET /api/v1/{team}/meta
```

or:

```http
GET /api/v1/{team}/version
```

Suggested response:

```json
{
  "api_version": "1.0.0",
  "deployment_version": "2026.08.04",
  "team_slug": "example-team",
  "status": "ok",
  "documentation_url": "https://app.tracezilla.com/api/documentation",
  "deprecated": false
}
```

The endpoint should:

- Require the normal bearer token.
- Confirm that the token can access the requested team.
- Return no partners, SKUs, lots, orders, or other business data.
- Be inexpensive and safe to use for connection validation.
- Clearly distinguish the public API contract version from the deployed
  application or build version.

The OpenAPI document currently identifies the specification as version
`1.0.0`, but an integration cannot use that value to confirm which API version
is serving an authenticated request.

## 2. Lightweight connection and health validation

If version metadata and service health should remain separate, provide:

```http
GET /api/v1/{team}/ping
```

Suggested response:

```json
{
  "status": "ok",
  "authenticated": true,
  "team_slug": "example-team"
}
```

This would let setup tools validate the base URL, token, and team selection
without requesting a page of SKUs or lots and discarding the result.

## 3. API compatibility and deprecation information

Return useful compatibility headers or metadata on API responses, for example:

```text
Tracezilla-API-Version: 1.0.0
Tracezilla-API-Deprecated: false
Tracezilla-API-Sunset: optional-date
```

Also maintain a machine-readable and human-readable API changelog that states:

- Additive changes.
- Breaking changes.
- Deprecated endpoints and fields.
- Planned removal dates.
- Required migration steps.

## 4. Token capability inspection

Provide a read-only endpoint that reports the current token's effective access
without exposing the token itself:

```http
GET /api/v1/{team}/token/capabilities
```

This could return named permissions or roles. It would allow an integration to
fail during setup with a useful message instead of discovering missing access
halfway through a synchronization.

## 5. Consistent pagination contract

Use and document one predictable pagination shape across list endpoints:

- Page-size parameter and maximum value.
- Stable next-page link or cursor behavior.
- Total count semantics.
- Sorting requirements for stable traversal.
- Behavior when records change while pages are being retrieved.

The OpenAPI schemas should include concrete pagination examples for every
supported pagination style.

## 6. Rate-limit and retry guidance

Document service limits and return actionable response headers where possible:

- Remaining request capacity.
- Reset or retry time.
- `Retry-After` for throttled responses.
- Whether limits apply per token, user, team, endpoint, or IP address.
- Recommended backoff behavior.

This information is important when inventory, catalog, and order integrations
process more than a small demonstration dataset.

## 7. Idempotency support for writes

Support an idempotency key for create operations, especially orders and other
records where an uncertain network response could otherwise lead to
duplicates:

```text
Idempotency-Key: integration-owned-unique-reference
```

Document:

- How long keys are retained.
- Whether a repeated key returns the original response.
- How key conflicts are reported.
- Which write endpoints support the behavior.

## 8. Structured and stable error responses

Use a common error schema across endpoints:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The request could not be accepted.",
    "fields": {
      "sku_code": ["The SKU code is already in use."]
    },
    "request_id": "req_123"
  }
}
```

Stable error codes, field errors, and a support request ID make failures safer
to automate and easier to diagnose than matching human-readable messages.

## 9. Request and correlation IDs

Return a unique request ID on every API response and accept an integration
correlation ID supplied by the client. Include both in integration logs so a
consultant and tracezilla support can investigate the same request without
sharing complete payloads.

## 10. Webhooks or change feeds

For workflows that should react to tracezilla changes, provide signed webhooks
or a cursor-based change feed for relevant events, such as:

- Inventory availability changed.
- SKU created or updated.
- Order status changed.
- Lot received or adjusted.

Document delivery guarantees, ordering, retries, signature verification, event
versions, and replay behavior.

## Suggested delivery order

1. Metadata/version or ping endpoint.
2. Stable error schema and request IDs.
3. Pagination and rate-limit documentation.
4. Token capability inspection.
5. Idempotency keys for creates.
6. Webhooks or change feeds.

The first item alone would immediately improve the setup experience: the
reference application could validate authentication, selected team, API
availability, and version without retrieving customer records.
