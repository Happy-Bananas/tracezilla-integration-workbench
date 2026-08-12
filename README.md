<p align="center">
  <img src="laravel-shopify-tracezilla-light.svg#gh-light-mode-only"
       alt="tracezilla Shopify Connector"
       width="600">

  <img src="laravel-shopify-tracezilla-dark.svg#gh-dark-mode-only"
       alt="tracezilla Shopify Connector"
       width="600">
</p>

# tracezilla Shopify Connector

A Laravel reference implementation demonstrating how to integrate Shopify with the tracezilla API.

 ### ⚠️ Important
> Before you can connect to Shopify and tracezilla, you must have valid accounts with both services..<br/>
there is guide for both in the documentation 




## Online Resources

- [Documentation, tutorials, and examples](https://happy-bananas.github.io/tracezilla-shopify-connector/)

## Quick Start (Docker)

```bash
git clone https://github.com/Happy-Bananas/tracezilla-shopify-connector.git
cd tracezilla-shopify-connector
docker compose up
```

- [Open the application on port 8000](http://localhost:8000)
- [Open the documentation on port 4000](http://localhost:4000/tracezilla-shopify-connector/)

### Configure and reload `.env`

The workbench reads credentials from the `.env` file in the repository root:

```text
tracezilla-integration-workbench/.env
```

If it does not exist yet, create it and generate the Laravel application key:

```bash
cp .env.example .env
docker compose run --rm --no-deps app php artisan key:generate
```

After changing credentials or any other `.env` value, restart the application
container so Laravel reloads the file:

```bash
docker compose restart app
```

Then refresh [http://localhost:8000](http://localhost:8000). The `.env` file is
ignored by Git and must never be committed because it contains secrets.

### Import Shopify SKUs into Tracezilla

Open the Tracezilla connection page and select **Import Shopify SKUs into
Tracezilla**. The import page checks that both API configurations are present.
It starts in dry-run mode and shows the synchronization result in the browser.

Disabling dry run requires an explicit confirmation because the operation can
create missing SKUs in the configured Tracezilla team. Review the demonstration
unit, weight, and conversion mapping before executing an import.

## Manual Installation

Prerequisites

Install the following software:

* PHP 8.3 or later
* Composer
* PostgreSQL 17
* Node.js and npm

Install Dependencies

```bash
composer install
npm install
```

Configure the Application

```bash
cp .env.example .env
php artisan key:generate
```

Update the database connection settings in .env, then run:

``` 
php artisan migrate 
```

Start the Development Environment

```bash
npm run dev
php artisan serve
```

## Do you need help?

You are welcome to contact me if you need help, I work for bananas.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.
