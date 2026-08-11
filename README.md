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
