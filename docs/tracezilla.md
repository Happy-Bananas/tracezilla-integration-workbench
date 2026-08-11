---
title: tracezilla Configuration
parent: Setup
nav_order: 20
layout: default
has_children: true
---

# tracezilla setup

Create a safe tracezilla demo environment, add the business data required by
the selected example, and authorize API access.

## Setup path

1. [Create a tracezilla demo account and warehouse](./tracezilla/tracezilla-account.html).
2. [Create the webshop partner](./tracezilla/webshop-partner.html) used by the
   order-import examples.
3. [Add received test inventory](./tracezilla/create-inventory.html) used by the
   inventory-synchronization example.
4. [Create and configure an API token](./tracezilla/authorize-api.html).
5. [Validate the tracezilla connection](./connection-validation.html).

## Expected result

At the end of tracezilla setup, the project should have:

- A demo account and the correct team slug.
- A test warehouse and its tracezilla location number.
- Any partner, SKU, and received inventory required by the selected workflow.
- An API token stored outside version control.
- A successful read-only connection check.

A demo account normally has a limited lifetime. Record its expiration date and
[contact tracezilla](https://www.tracezilla.com/en/contact-us) if an integration
project requires an extension.
