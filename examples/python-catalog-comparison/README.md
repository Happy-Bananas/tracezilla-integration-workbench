# Python catalog comparison

A framework-neutral, read-only comparison of Shopify product-variant SKUs and
tracezilla SKU codes. It uses only the Python standard library. Docker Desktop,
OrbStack, and other Docker Compose-compatible runtimes can run it without a
local Python installation.

## Run

```bash
cp .env.example .env
# Fill in sandbox credentials in .env.
docker compose run --rm --build catalog-comparison
```

## Test

```bash
docker compose run --rm --build test
```

The tests use fake HTTP transports and require neither credentials nor network
access. The command prints JSON and contains no API write operation.
