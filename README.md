# tracezilla Integration Workbench

A local consultant tool for validating Shopify and Tracezilla credentials and
inspecting a small sample of integration data.

The workbench is not a production connector or an implementation template. Its
initial capabilities are deliberately read-only: Shopify products and locations,
and Tracezilla SKUs.

## Run locally

Requirements: Docker with Docker Compose.

```bash
docker compose up --build
```

Open <http://localhost:8000/>.

Stop the workbench with:

```bash
docker compose down
```

## Security boundary

The workbench is intended for trusted local use. Credentials are accepted
through the browser, stored in an encrypted cookie session for at most 60
minutes, never rendered back into password fields, and removable through a
forget action. Do not expose the development server to the public internet.

## Test

```bash
docker compose run --rm -e APP_ENV=testing -e SESSION_DRIVER=array app php artisan test
```

## License

This project is available under the [MIT License](./LICENSE).
