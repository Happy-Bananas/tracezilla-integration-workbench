---
title: Clients
parent: Reference
grand_parent: Laravel
nav_order: 60
layout: default
---

# Clients

Clients are the lowest application layer that communicates directly with Shopify and tracezilla.

This project contains two API clients:

```text
app/Clients/ShopifyClient.php
app/Clients/TracezillaClient.php
```

Their main job is to turn configuration and credentials into authenticated Laravel HTTP clients that services can reuse.

## Where Clients Fit

The catalog architecture flows through these layers:

```text
Artisan command
    ↓
Catalog synchronization feature
    ↓
Catalog and SKU services
    ↓
ShopifyClient and TracezillaClient
    ↓
External APIs
```

A service asks a client to communicate with an API. The service does not need to repeat authentication headers or construct the base URL for every request.

## What a Client Is Responsible For

The clients in this project handle:

- Reading API configuration from Laravel.
- Validating required configuration.
- Constructing base URLs.
- Authentication.
- Standard request headers.
- Connection and request timeouts.
- HTTP errors.
- API-level errors that HTTP status codes do not describe.

A client should not decide:

- Which products should be synchronized.
- How Shopify fields map to tracezilla fields.
- Whether an existing SKU should be skipped or updated.
- Whether a synchronization is a dry run.

Those are feature and business rules that belong in higher layers.

## Laravel's HTTP Client

Both clients use Laravel's HTTP client:

```php
use Illuminate\Support\Facades\Http;
```

Laravel creates a `PendingRequest`, which stores settings that should be applied to future requests:

```php
$http = Http::baseUrl('https://example.test')
    ->acceptJson()
    ->timeout(30);
```

The request has not been sent yet. It is "pending" until code calls a method such as:

```php
$http->get('/products');
$http->post('/skus', $payload);
```

This is why both clients expose an `http()` method returning `PendingRequest`.

## Shopify Client

`ShopifyClient` performs two steps.

### 1. Authentication

When Laravel creates the client, it sends a form request to:

```text
https://SHOPIFY_SHOP_URL/admin/oauth/access_token
```

The request uses the configured client ID, client secret, scope, and the `client_credentials` grant.

Shopify returns an access token. The client checks that the response contains a non-empty `access_token` before continuing.

### 2. Authenticated API Client

The access token is added to subsequent requests using the header:

```text
X-Shopify-Access-Token: ACCESS_TOKEN
```

The configured base URL is:

```text
https://SHOPIFY_SHOP_URL/admin/api/SHOPIFY_API_VERSION
```

The API version is configuration rather than hardcoded application logic. This makes future Shopify API upgrades visible and controlled.

### Sending GraphQL Requests

Services send GraphQL operations through:

```php
$response = $shopifyClient->graphql(
    query: GetProductVariants::QUERY,
    variables: [
        'first' => 250,
        'after' => null,
    ],
);
```

The client sends the query and variables to:

```text
POST /graphql.json
```

The method returns the decoded response as an array.

## Shopify GraphQL Errors

An HTTP request can succeed while the GraphQL operation fails. Shopify may return HTTP 200 with an error body:

```json
{
  "data": null,
  "errors": [
    {
      "message": "The query is invalid."
    }
  ]
}
```

Laravel's `throw()` method only detects unsuccessful HTTP status codes. `ShopifyClient::graphql()` therefore also inspects the `errors` field.

If GraphQL errors exist, it throws:

```text
App\Clients\Exceptions\ShopifyGraphQlException
```

The exception provides:

- A readable message containing up to three GraphQL error messages.
- An `errors()` method containing the original error array for structured handling and tests.

The client also throws this exception if Shopify returns a response that cannot be decoded into an array.

## tracezilla Client

`TracezillaClient` does not need a separate authentication request. It creates an authenticated base client using:

```text
TRACEZILLA_BASE_URL/api/v1/TRACEZILLA_TEAM_SLUG
```

The API key is sent as a bearer token:

```text
Authorization: Bearer TRACEZILLA_API_KEY
```

A service can use it like this:

```php
$response = $tracezillaClient
    ->http()
    ->get('/skus', [
        'perPage' => 250,
    ])
    ->throw()
    ->json();
```

Endpoint selection and pagination belong in services such as `TracezillaSkuService`, not in the generic client.

## Configuration

The clients read configuration from:

```text
config/services.php
```

Environment variables belong in `.env`. Application code should use `config()` rather than calling `env()` directly.

The canonical names, meanings, and secret classifications live in
[Configuration Variables](../reference/configuration.html) and
[Authentication and Secrets](../reference/authentication-and-secrets.html).
The Laravel clients normalize trailing slashes before constructing API URLs;
`ShopifyClient` also accepts a shop hostname prefixed by `https://`.

## Configuration Errors

The clients validate required values before creating requests. Missing configuration produces:

```text
App\Clients\Exceptions\ClientConfigurationException
```

For example:

```text
Missing required client configuration [services.shopify.client_secret].
```

Timeouts must be positive integers. An invalid value produces a message such as:

```text
Client configuration [services.tracezilla.timeout] must be a positive integer.
```

These messages are more useful than a low-level PHP type error and do not reveal credential values.

## HTTP Errors

Requests use Laravel's `throw()` method. HTTP errors therefore produce Laravel's:

```text
Illuminate\Http\Client\RequestException
```

Higher layers can catch that exception to report the status and safe response details.

Never display or log complete request headers, configuration arrays, client secrets, API keys, or access tokens.

## Timeouts and Retries

Both clients use explicit connection and request timeouts. A connection timeout limits how long establishing the network connection can take. The normal timeout limits the complete request duration.

The clients do not automatically retry every request.

This is deliberate. Retrying a read is normally safer than retrying a write
that may already have reached the external API. An adopting application should
add retries at the service or operation level only when that operation's
idempotency and customer requirements are understood.

## Dependency Injection

Laravel can create a service and automatically inject its client:

```php
class ShopifyCatalogService
{
    public function __construct(
        protected ShopifyClient $client,
    ) {
    }
}
```

The command does not need to construct either object manually. Laravel follows the constructor dependencies and creates them through its service container.

## Testing the Clients

The direct client tests are located at:

```text
tests/Feature/ShopifyClientTest.php
tests/Feature/TracezillaClientTest.php
```

They verify:

- URL construction.
- Authentication headers.
- Configurable Shopify API versions.
- Missing and invalid configuration.
- Missing Shopify access tokens.
- GraphQL errors returned with HTTP 200.
- Invalid GraphQL JSON responses.

All external requests are replaced with Laravel HTTP fakes. `Http::preventStrayRequests()` ensures a test fails instead of contacting a real API.

Run only the client tests:

```bash
php artisan test \
  tests/Feature/ShopifyClientTest.php \
  tests/Feature/TracezillaClientTest.php
```

Run the complete suite:

```bash
php artisan test
```

## Reusing the Clients

Developers copying the clients into another Laravel application need these files:

```text
app/Clients/ShopifyClient.php
app/Clients/TracezillaClient.php
app/Clients/Exceptions/ClientConfigurationException.php
app/Clients/Exceptions/ShopifyAuthenticationException.php
app/Clients/Exceptions/ShopifyGraphQlException.php
```

They must also copy the relevant `services.php` configuration entries and environment-variable examples.

The clients do not depend on catalog commands, controllers, views, or tracezilla SKU mapping rules. This keeps them reusable across catalog, inventory, location, and order features.
