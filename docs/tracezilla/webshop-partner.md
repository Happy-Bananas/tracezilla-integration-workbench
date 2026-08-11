---
layout: default
nav_order: 20
parent: tracezilla Configuration
grand_parent: Setup
title: Create the Webshop Partner
---

# Create the webshop partner

This setup is required by the Laravel [Import Individual
Orders](../guides/import-individual-orders.html) recipe. The maintained
Laravel example resolves the partner by name and uses its primary location as
the customer location on each new sales order.

Before adding inventory for the Shopify example, create a partner that
represents the webshop in tracezilla.

This guide uses:

```text
Partner name: Banana primary webshop
Payment term: Custom
Payment text: Stripe
```

These are demonstration values. Use the customer's webshop name and payment
arrangement in a real integration.

## Open partners

1. Sign in to tracezilla.
2. Open **Partners** from the main navigation.
3. Select **Add Partner**.

<!-- Screenshot suggestion:
     Show the Partners page with the Add Partner button highlighted. -->

## Enter basic information

In the **Basic information** section:

1. Find the partner name field.
2. Enter:

   ```text
   Banana primary webshop
   ```

The name should make it clear that this partner represents the Shopify
webshop.

Make sure the partner can be used as a **Customer** and has a primary location.
The order-import example resolves this partner by its exact name and uses its
primary location as the customer location.

Select an **Owner** for the partner as well. tracezilla requires an owner on
the sales order, and this example copies it from the webshop partner.

<!-- Screenshot suggestion:
     Show the Basic information section with Banana primary webshop entered. -->

## Configure the payment term

1. Open the **Prices, conditions...** section.
2. Find **Default payment term**.
3. Select **Custom**.
4. In the **Enter text** field, enter the payment provider:

   ```text
   Stripe
   ```

Selecting **Custom** enables a description that reflects how webshop payments
are handled. `Stripe` is only an example; replace it with the provider or
payment policy used by the customer.

<!-- Screenshot suggestion:
     Show Default payment term set to Custom and Stripe in Enter text. -->

## Save and verify

Select **Save**, then confirm that the partner appears in the partner list as:

```text
Banana primary webshop
```

The webshop partner is now ready for the next tracezilla setup step.

Next: [Add inventory to tracezilla](create-inventory.html).

## Checklist

- The partner has a recognizable webshop name.
- The partner can be used as a customer and has an owner.
- The partner has a primary location.
- **Default payment term** is set to **Custom**.
- The custom text names the example payment provider, ie. **Stripe**.
- The partner has been saved and appears in the partner list.
