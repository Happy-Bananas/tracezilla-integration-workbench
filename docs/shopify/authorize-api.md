---
title: Authorize API
parent: Shopify Setup
grand_parent: Setup
nav_order: 50
layout: default
---

# Create and install a Shopify app

The integration needs an app that declares its Admin API permissions and is
installed on the development store. Creating an app, releasing a version, and
installing that version are separate steps.

## Choose the minimum access scopes

Grant only the access needed by the workflows selected for the integration:

| Use case | Shopify access scope |
|---|---|
| Read products and variants | `read_products` |
| List locations | `read_locations` |
| Read inventory | `read_inventory` |
| Set inventory | `write_inventory` plus the required read access |
| Read recent orders | `read_orders` |

For example, catalog inspection needs `read_products`; it does not need order
or inventory-write access.

## Create the app

1. Open the [Shopify development dashboard](https://dev.shopify.com/dashboard).
2. Select the Partner organization used for the development store.
3. Open **Apps** and choose **Create app**.
4. Give the app a descriptive name such as `tracezilla integration test`.
5. Open the app's version or configuration area.
6. Select the minimum scopes from the table above.
7. Give the version a meaningful name and release it.

If you later change scopes, create and release another app version. A released
version declares what the app requests; it does not silently grant new access
to an existing store installation.

## Install the app on the development store

1. Open the app's installation area from the development or Partner dashboard.
2. Choose **Install app**.
3. Select the development store created for this project.
4. Review the requested permissions carefully.
5. Confirm the installation.

If you release a version with additional scopes later, return to Shopify Admin
and refresh the installed app:

1. Open **Settings**, then **Apps and sales channels** in the target store.
2. Open the integration app.
3. If Shopify displays an **Update** or permission-review action, select it and
   approve the new permissions.
4. Reload the app page after approval so the store uses the new release.

Releasing the app version in the development dashboard is not always enough by
itself. The installed copy can continue using its previous permissions until
the store reviews the update. Reinstall or rotate credentials only if
Shopify's current workflow requires it.

## Store the credentials

Open the app's settings and locate its **Client ID** and **Client secret**.
Store them in a password manager or secret store. Do not place credentials in
documentation, screenshots, committed files, or terminal output.

Add the test values to the project's uncommitted `.env` file:

```dotenv
SHOPIFY_SHOP_URL=example-integration-test.myshopify.com
SHOPIFY_CLIENT_ID=your-client-id
SHOPIFY_CLIENT_SECRET=your-client-secret
SHOPIFY_SCOPE=read_products,read_locations
SHOPIFY_API_VERSION=2025-10
```

Choose `SHOPIFY_SCOPE` to match the released app version and the example being
used. The environment variable requests scopes during token acquisition; it
cannot grant scopes that the installed app has not approved.

## Verify token acquisition without exposing the token

The safest project-level check is the Laravel validator described in
[Validate Connections](../connection-validation.html). It requests the token
and uses it without printing the secret.

If you need to diagnose authentication independently, send a form request to:

```text
https://SHOPIFY_SHOP_URL/admin/oauth/access_token
```

with the `client_credentials` grant, client ID, client secret, and required
scope. Treat the returned access token as a secret and do not save the response
in documentation or shell history.

## Verify the result

Before continuing, confirm that:

- The app has a released version.
- The version declares only the required scopes.
- The app is installed on the intended development store.
- The store has approved the active scopes.
- The installed app has been updated and reloaded after the latest release.
- The client ID and secret are stored outside version control.
- The Shopify connection validator succeeds.
