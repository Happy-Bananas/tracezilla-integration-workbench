---
layout: default
nav_order: 10
parent: tracezilla Configuration
grand_parent: Setup
title: tracezilla Account
---

# Create a tracezilla demo account

Use a tracezilla demo account while learning and validating an integration.
This keeps test partners, SKUs, orders, and inventory separate from customer
production data.

## Create the account

1. Open the tracezilla registration or demo-account page.
2. Register with your work contact details.
3. Verify the account if tracezilla sends a verification message.
4. Enter the requested company information.
5. Create or select the team that will own the integration test data.

A demo account normally has a limited lifetime. Record its expiration date and
request an extension from tracezilla if the integration project needs more
time.

## Create the first location

The examples need a location that can represent a warehouse:

1. Open the partner or location area in tracezilla.
2. Choose **Add location**.
3. Enter a clear test name and the required address information.
4. Enable the settings required for the location to hold warehouse inventory.
5. Save the location.
6. Record the location number shown by tracezilla. The inventory examples use
   this number to select the source warehouse.

Use a recognizable name such as `Integration Test Warehouse`. Do not assume
that the first company or supplier location is the intended inventory source;
verify where received lots actually appear.

## Verify the result

Confirm that:

- You are signed in to the intended demo team.
- The team and company details are complete.
- A test warehouse location exists.
- You know its tracezilla location number.
- The account contains no customer production data.

Next, [create the webshop partner](webshop-partner.html) or
[authorize API access](authorize-api.html).
