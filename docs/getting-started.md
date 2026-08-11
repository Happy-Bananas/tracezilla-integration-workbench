---
title: Install and Run
parent: Laravel
nav_order: 5
layout: default
has_children: true
---

# Install and run Laravel

Install the Laravel application and its documentation site. Account and API
configuration continues in [Setup](./setup.html) after the local project is
running.

## Prerequisites

The recommended setup requires:

- Git.
- Docker with Docker Compose.

For a manual installation, use PHP 8.3 or later, Composer, Node.js with npm,
and a supported local database.

## Clone the repository

```bash
git clone https://github.com/Happy-Bananas/tracezilla-shopify-connector.git
cd tracezilla-shopify-connector
```

## Create local configuration

Create the uncommitted environment file before starting the services:

```bash
cp .env.example .env
```

Keep `.env` outside version control. Shopify and tracezilla credentials are
added later through the setup guides.

## Start with Docker

```bash
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up
```

The project starts:

| Service | Address |
|---|---|
| Laravel application | [http://localhost:8000](http://localhost:8000) |
| Documentation site | [http://localhost:4000/tracezilla-shopify-connector/](http://localhost:4000/tracezilla-shopify-connector/) |
| PostgreSQL | `localhost:5432` |

The first setup can take longer while Docker builds the application image and
Composer installs dependencies.

Run Artisan inside the application container:

```bash
docker compose exec app php artisan --version
docker compose exec app php artisan test
```

## Manual installation

If the required local runtimes are already installed:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

The repository's default `.env.example` uses SQLite for a simple manual setup.
Docker provides PostgreSQL; configure the Docker database values in `.env` if
the application needs persistence there. The API examples themselves do not
require application database records.

## Verify the installation

Before adding external credentials, confirm:

- The Laravel homepage opens.
- The documentation site opens at the path shown above.
- `php artisan test` succeeds locally or in Docker.
- `.env` is not staged by Git.

Next, complete [Setup](./setup.html), then choose a Laravel
[Example](./guides.html).

Before running an example, [Validate
Connections](./platforms/laravel-connection-validation.html).
