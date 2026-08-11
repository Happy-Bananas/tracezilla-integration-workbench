# TypeScript catalog comparison

A framework-neutral, read-only comparison of Shopify product-variant SKUs and
tracezilla SKU codes. Docker contains Node.js, TypeScript, and all dependencies.
Docker Desktop, OrbStack, and other Docker Compose-compatible runtimes can run
the same commands.

## Run

```bash
cp .env.example .env
# Fill in the sandbox credentials in .env.
docker compose run --rm catalog-comparison
```

The command prints JSON and never calls a catalog, inventory, or order write
endpoint. Keep `.env` private; it is ignored by Git and excluded from the image.

## Test

```bash
docker compose run --rm test
```

Tests use mocked HTTP responses and do not require credentials or network API
access.
