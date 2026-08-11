---
title: Mappers
parent: Reference
grand_parent: Laravel
nav_order: 70
layout: default
---

# Mappers

A mapper translates data from one system's meaning and structure into another system's meaning and structure.

The catalog synchronization uses:

```text
app/Features/CatalogSynchronization/Mappers/
└── ShopifyVariantToTracezillaSkuMapper.php
```

Its complete responsibility is expressed by its name:

```text
ShopifyVariantData → TracezillaSkuData
```

The inventory synchronization demonstrates the opposite direction:

```text
TracezillaInventoryData → Shopify available quantity
```

That policy lives in
`TracezillaInventoryToShopifyQuantityMapper`. It is especially important to
replace because units, traceability, reservations, bundles, and sellable stock
are customer-specific decisions.

## Why Use a Dedicated Mapper?

Shopify and tracezilla describe products differently. A Shopify product variant contains values such as:

- GraphQL variant ID.
- Legacy numeric ID.
- SKU.
- Price.

A tracezilla SKU creation request needs values such as:

- SKU code.
- Global name.
- Net and gross weight factors.
- Unit of measure.
- Lot unit.
- Default unit conversion.

The APIs do not provide a natural one-to-one copy of every field. Someone must decide how the source values become destination values. That decision is a business rule, so it belongs in one clearly named mapper rather than inside a command or API client.

## Example Mapping Rules

The example intentionally uses simple values in the mapper:

| Shopify source | tracezilla destination | Example rule |
|---|---|---|
| `sku` | `sku_code` | Copy the Shopify SKU |
| `sku` | `global_name` | Use the SKU as the initial name |
| Mapper value | `weight_factor_net` | `1.0` |
| Mapper value | `weight_factor_gross` | `1.0` |
| Mapper value | `unit_of_measure` | `pcs` |
| Mapper value | `lot_unit` | `colli` |
| Mapper value | `default_uom_conversion` | `1.0` |

These values make the API example concrete; they are not recommended defaults
for every product or customer. The adopting developer must replace them with
appropriate mapping rules.

The Shopify GraphQL ID, legacy ID, and price are not included in the tracezilla
SKU creation payload. They remain available on `ShopifyVariantData` for other
mapping policies.

## Using the Mapper

Laravel injects the mapper into the synchronization action:

```php
public function __construct(
    private ShopifyCatalogService $shopifyCatalog,
    private TracezillaSkuService $tracezillaSkus,
    private ShopifyVariantToTracezillaSkuMapper $skuMapper,
) {}
```

One Shopify variant becomes one typed tracezilla SKU:

```php
$skuData = $skuMapper->map($variant);
```

The synchronization action can use the typed result:

```php
$skuData->skuCode;
$skuData->toApiPayload();
```

In the running application, `TracezillaSkuService` accepts the object and performs the final payload conversion:

```php
$tracezillaSkus->createSku($skuData);
```

## Runtime Data Flow

```text
Shopify GraphQL node
    ↓ ShopifyVariantData::fromApiResponse()
ShopifyVariantData
    ↓ ShopifyVariantToTracezillaSkuMapper::map()
TracezillaSkuData
    ↓ TracezillaSkuData::toApiPayload()
tracezilla POST /skus
```

Every boundary now has one explicit conversion:

1. Shopify API array to a typed source object.
2. Source object to a typed destination object.
3. Destination object to a tracezilla API payload.

## Keep the Mapping Visible

The business mapping lives directly in `map()`:

```php
return new TracezillaSkuData(
    skuCode: $variant->sku,
    globalName: $variant->sku,
    weightFactorNet: 1.0,
    weightFactorGross: 1.0,
    unitOfMeasure: 'pcs',
    lotUnit: 'colli',
    defaultUomConversion: 1.0,
);
```

This avoids hiding product semantics behind global environment variables.
Units, conversions, and weights often vary by SKU, so a single deployment-wide
value can be misleading.

A real integration can replace these values with Shopify metafields, tracezilla
partner mappings, a database table, or customer-specific policy objects.
Laravel can construct this mapper automatically because it has no configuration
dependencies.

## Validation

A Shopify variant without an SKU cannot become a tracezilla SKU. Calling `map()` for such a variant throws an `InvalidArgumentException` before any API request is sent.

`TracezillaSkuData` also validates that required destination strings are not blank.

The synchronization action collects invalid variants in the structured result.
The mapper only reports that it cannot perform the requested conversion.

## Customizing the Mapping

Developers can change mapping policy in one class without changing authentication, pagination, commands, or HTTP requests.

Examples include:

- Use a Shopify title for `global_name` after adding it to the GraphQL query and data object.
- Select units from Shopify metafields.
- Calculate weight factors from product data.
- Use different mapping classes for different product categories.
- Add optional tracezilla fields to `TracezillaSkuData` and its payload.

If the mapping becomes conditional, keep the decision visible in the mapper or split it into smaller mapping strategies. Do not move those rules into `ShopifyClient` or `TracezillaClient`.

## Testing

The plain-PHP unit test verifies:

- The complete example mapping.
- A variant without an SKU is rejected.

```bash
php artisan test \
  tests/Unit/Features/CatalogSynchronization/ShopifyVariantToTracezillaSkuMapperTest.php
```

The command test verifies that the complete mapped payload reaches the fake tracezilla API.

## Files Required for Reuse

To reuse this mapping slice in another Laravel project, copy:

```text
app/Features/CatalogSynchronization/Data/ShopifyVariantData.php
app/Features/CatalogSynchronization/Data/TracezillaSkuData.php
app/Features/CatalogSynchronization/Mappers/ShopifyVariantToTracezillaSkuMapper.php
```

Also copy the focused mapper test as an executable mapping specification.
