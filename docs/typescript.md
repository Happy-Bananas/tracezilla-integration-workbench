---
title: TypeScript
nav_order: 50
layout: default
has_children: true
---

# TypeScript

## Prerequisites

- Git and a Docker Compose-compatible runtime such as OrbStack or Docker
  Desktop.
- Shopify and tracezilla sandbox credentials.

The project is in:

```text
examples/typescript-catalog-comparison/
```

It is a command-line container, so there is no server to leave running. Build
and execute it from the project directory:

```bash
cd examples/typescript-catalog-comparison
cp .env.example .env
docker compose run --rm --build catalog-comparison
```

## Example

| Example | Main files | Command |
|:--|:--|:--|
| [Compare Catalogs](./platforms/typescript-docker-catalog-comparison.html) | `src/cli.ts`, `src/catalog/compare-catalogs.ts`, `src/clients/` | `docker compose run --rm --build catalog-comparison` |
