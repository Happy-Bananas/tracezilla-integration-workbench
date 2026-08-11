---
title: Automating Synchronization
parent: Reference
grand_parent: Laravel
nav_order: 30
layout: default
---

# Automating synchronization

The synchronization examples can be started manually with Artisan, but a
production integration usually needs to react without a person running a
command. There are two common ways to start that work:

- A **Shopify webhook** starts work soon after something changes.
- A **scheduled reconciliation** regularly compares the complete state of both
  systems.

These mechanisms solve different problems. A robust integration commonly uses
both.

## Webhooks: Shopify pushes a notification

A webhook is an HTTP request sent by Shopify to this application after an event
such as a product, order, or inventory change.

```text
Merchant changes data in Shopify
              ↓
Shopify sends an HTTP POST request
              ↓
Laravel verifies and accepts the webhook
              ↓
Laravel queues the synchronization work
              ↓
A queue worker processes the change
```

Webhook topics include product, order, and inventory events. The application
must subscribe to the topics it needs and expose a public HTTPS endpoint that
Shopify can reach.

A webhook handler should:

1. Verify Shopify's HMAC signature before trusting the request.
2. Deduplicate deliveries using the webhook ID.
3. Record or dispatch the work to a queue.
4. Return a successful HTTP response quickly.
5. Make the queued operation safe to retry.

Do not put a slow, full-catalog synchronization directly in the HTTP handler.
Shopify may retry a delivery, and the endpoint needs to respond promptly.

See Shopify's [webhook documentation](https://shopify.dev/docs/apps/build/webhooks)
for subscription, verification, delivery, and retry details.

## Scheduled jobs: the application checks periodically

A scheduled job is initiated by this application rather than Shopify. Laravel's
scheduler can run an Artisan command at a chosen interval.

This repository has the scheduler entry point in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('pull-catalog-from-shopify')->daily();
}
```

This only defines the schedule. Something on the server must invoke Laravel's
scheduler. On a traditional server, cron commonly runs this every minute:

```cron
* * * * * cd /path/to/app && php artisan schedule:run
```

Laravel then decides whether each configured task is due. Cron does not need a
separate entry for every Artisan command.

In a Docker deployment, prefer a dedicated scheduler container or process. It
can run the same application image and environment as the web application,
without installing a cron daemon in the web container.

Do not enable an automated write command until its source of truth, mapping,
credentials, error reporting, and retry behavior have been verified. Start
with a read-only comparison or dry run.

## Why webhooks are not the whole solution

Webhooks make synchronization fast, but they should not be treated as a
perfect historical record. A delivery can be delayed or duplicated, the
application can be unavailable, or processing can fail after the endpoint has
accepted the request.

A scheduled comparison asks a different question:

> Do Shopify and tracezilla agree now?

That makes it useful for discovering drift regardless of which earlier event
caused it.

## Recommended design

Use webhooks for responsiveness and scheduled reconciliation as a safety net:

```text
Shopify webhook
      ↓
Queue a small synchronization job
      ↓
Apply the change quickly

             plus

Nightly Laravel schedule
      ↓
Compare the complete catalogs
      ↓
Report or safely repair differences
```

The webhook path should be narrow and retryable. The scheduled path should
inspect the current state and detect anything the event-driven path missed.

For the catalog example, this command is a suitable starting point for the
scheduled safety check because it does not write data:

```bash
php artisan pull-catalog-from-shopify
```

Inside the Docker environment:

```bash
docker compose exec app php artisan pull-catalog-from-shopify
```

When scheduling a command, add monitoring as well. A task that runs silently
but whose failures are never noticed is not a reliable synchronization system.

## Where the feature classes fit

An Artisan command is only one interface to the synchronization logic. A
console command, webhook handler, queued job, or scheduled job can construct
the same options object and invoke the same action:

```text
Console command ─┐
Webhook job ─────┼──→ Synchronization action ──→ Shopify and tracezilla services
Scheduler ───────┘
```

This is why validation also lives in option objects such as
`CatalogComparisonOptions`: every caller gets the same valid input rules, not
only the console command.
