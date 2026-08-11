---
title: Home
layout: home
nav_order: 0
---



<p align="left">
  <img
    src="{{ '/assets/images/laravel-shopify-tracezilla-dark.svg' | relative_url }}"
    alt="Create app"
    width="1374"
    align="left">
</p>

<br/>
<br/>
<br/>



# Shopify and tracezilla integration guide

Practical integration examples you can copy and adapt to your customer's
needs.

## What is this?

The tracezilla Shopify Connector demonstrates selected integration patterns
between Shopify and tracezilla.

It is inspiration and a starting point—not a universal connector. You are
responsible for adapting field mappings, units, locations, prices, inventory
rules, and production safeguards to the customer.

Read [Project Scope](./project-scope.html) before adapting an example.

## Who is this for?

This documentation is for you if:

- You need to integrate Shopify with tracezilla.
- You are an integration consultant or developer choosing an implementation.
- You prefer learning by running real code.
- You want a foundation for your own integration.

## Choose your path

- [Getting Started](./start-here.html): understand the project and choose an
  implementation.
- [Setup](./setup.html): prepare Shopify and tracezilla test environments and
  validate both API connections.
- [Laravel](./laravel-reference.html), [Make.com](./make.html),
  [TypeScript](./typescript.html), or [Python](./python.html): open cookbook
  examples and platform-specific references.
- [Reference](./reference.html): find shared integration concepts and technical
  details.
- [Troubleshooting](./guides/troubleshooting.html): diagnose setup and recipe
  failures.

## What do I need?

Prerequisites:

- Git
- Docker (recommended but not required)
- A Shopify Partner account
- A tracezilla demo account

Docker is recommended to make getting started easier, but it is not a requirement.

## New to the complete project?

1. [Read the project scope](./project-scope.html).
2. [Choose an implementation](./choose-implementation.html).
3. [Complete Shopify and tracezilla setup](./setup.html).
4. [Validate both API connections](./connection-validation.html).
5. Open your platform and run its read-only **Compare Catalogs** example.

At every mapping step, ask: **Does this assumption make sense for this
customer?**


May your syncs always be successful. 🚀
