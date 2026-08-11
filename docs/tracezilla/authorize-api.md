---
layout: default
parent: tracezilla Configuration
grand_parent: Setup
nav_order: 40
title: Authorize API
---

# Authorize the tracezilla API

The connector needs the tracezilla base URL, team slug, and an API token. Use
credentials from a demo team while setting up the project.

## Find the team slug

1. Sign in to tracezilla.
2. Select the demo team used for this project.
3. Inspect the browser address after opening a page inside that team.
4. Identify the team-specific slug in the URL and record it.

The slug identifies the team in API paths. It is not necessarily identical to
the displayed company name.

## Create an API token

1. Open your account menu in tracezilla.
2. Open **My Account** or the equivalent personal settings page.
3. Open **API Tokens**.
4. Create a token for this integration and select the permissions required by
   the selected examples.
5. Copy the token immediately and store it in a password manager or secret
   store; it may only be displayed once.

Use the least-privileged role that supports the selected workflow. If an Admin
token is used for initial testing, review and reduce its access before adopting
the integration for production.

Never commit a token, paste it into documentation, or include it in screenshots
and support messages.

## Configure the reference application

Create `.env` from `.env.example` if it does not already exist, then set:

```dotenv
TRACEZILLA_BASE_URL=https://app.tracezilla.com
TRACEZILLA_TEAM_SLUG=your-team-slug
TRACEZILLA_API_KEY=your-api-token
TRACEZILLA_TIMEOUT=30
TRACEZILLA_CONNECT_TIMEOUT=10
```

Clear cached Laravel configuration after changing these values:

```bash
php artisan config:clear
```

If PHP runs inside Docker, use:

```bash
docker compose exec app php artisan config:clear
```

## Verify the result

Use the read-only tracezilla check in
[Validate Connections](../connection-validation.html). Do not continue until:

- The selected demo team is correct.
- The team slug resolves successfully.
- The API token authenticates.
- The client can read one expected API resource.

Example-specific warehouse numbers, partner names, tags, prefixes, and mapping
choices belong to the workflow that demonstrates them, not to global client
configuration.
