---
title: Authentication and Secrets
parent: Reference
nav_order: 10
layout: default
---

# Authentication and secrets

Shopify and tracezilla use different authentication mechanisms, but their
credentials require the same handling: least privilege, storage outside source
control, no display in output, and rotation after suspected exposure.

## Shopify

The examples use a client ID and client secret to request a short-lived Admin
API access token from the store's token endpoint. The access token is then sent
to the versioned Admin API. Treat the client secret and every returned access
token as secrets.

Shopify permissions have two parts:

1. The released app version declares scopes.
2. The store installation reviews and approves them.

Changing a local scope variable cannot grant access. After releasing new
scopes, update or review the installed app in Shopify Admin and reload it.

## tracezilla

The examples send a tracezilla API token as a Bearer token to the API path for
one team slug. Treat the API token as a secret. The team slug identifies the
destination team but is not an authentication credential by itself.

Use a demo team while developing and the least-privileged token capable of the
selected workflow.

## Storage and output rules

- Use an uncommitted `.env` file for local Docker examples or the platform's
  encrypted secret store for hosted automation.
- Keep real values out of code, Git, screenshots, scenario exports, support
  messages, command history, logs, and exception details.
- Do not print token responses, authorization headers, or complete
  configuration arrays in connection tests.
- Redact credentials before sharing a request or error.
- Rotate a credential immediately if it entered Git history or another
  uncontrolled location; removing the visible file is not sufficient.

Connection validation should make one minimal authenticated read and return
only useful non-secret evidence. See the Laravel [Validate Connections
recipe](../platforms/laravel-connection-validation.html).
