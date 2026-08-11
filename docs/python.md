---
title: Python
nav_order: 60
layout: default
has_children: true
---

# Python

## Prerequisites

- Git and a Docker Compose-compatible runtime such as OrbStack or Docker
  Desktop.
- Shopify and tracezilla sandbox credentials.

The project is in:

```text
examples/python-catalog-comparison/
```

It is a command-line container, so there is no server to leave running. Build
and execute it from the project directory:

```bash
cd examples/python-catalog-comparison
cp .env.example .env
docker compose run --rm --build catalog-comparison
```

## Example

| Example | Main files | Command |
|:--|:--|:--|
| [Compare Catalogs](./platforms/python-docker-catalog-comparison.html) | `src/cli.py`, `src/comparison.py`, `src/clients.py` | `docker compose run --rm --build catalog-comparison` |
