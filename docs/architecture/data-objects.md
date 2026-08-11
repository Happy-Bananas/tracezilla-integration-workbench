---
title: Data Objects
parent: Reference
grand_parent: Laravel
nav_order: 50
layout: default
---

# Data Objects

This page explains why the project uses data objects and introduces `ShopifyVariantData`. It assumes no previous knowledge of advanced PHP or Laravel.

## The Problem With Raw Arrays

Shopify returns product variants as JSON. Laravel converts that JSON into PHP arrays that look similar to this:

```php
[
    'id' => 'gid://shopify/ProductVariant/123',
    'legacyResourceId' => '123',
    'sku' => 'BANANA-001',
    'price' => '10.00',
]
```

Arrays are convenient, but an array does not explain its own rules:

- Which fields are required?
- Can the SKU be missing?
- Is the legacy ID a number or a string?
- What happens if `price` is absent?
- Is a misspelled key detected immediately?

Code using raw arrays accesses fields with strings:

```php
$sku = $variant['sku'];
```

If `sku` is misspelled or absent, the problem may not be discovered until the synchronization is running.

## The Purpose of `ShopifyVariantData`

`ShopifyVariantData` gives one Shopify variant a documented shape:

```php
final readonly class ShopifyVariantData
{
    public function __construct(
        public string $graphQlId,
        public string $legacyId,
        public ?string $sku,
        public string $price,
    ) {
    }
}
```

Instead of remembering array keys, code can use named properties:

```php
$variant->sku;
$variant->price;
$variant->legacyId;
```

An editor can autocomplete these property names, and PHP can check their types.

## PHP Syntax Explained

### `final`

`final` means another class cannot extend `ShopifyVariantData` and silently change what it represents. This keeps the data contract predictable.

### `readonly`

`readonly` means the property values cannot be changed after the object is created.

This is useful for API data. One Shopify response should describe one stable snapshot of a variant:

```php
$variant->sku = 'CHANGED'; // PHP will reject this.
```

If different data is needed, create a new object instead of modifying the existing one.

### Constructor Property Promotion

The properties are declared directly in the constructor:

```php
public function __construct(
    public string $graphQlId,
    public string $legacyId,
    public ?string $sku,
    public string $price,
)
```

This shorter PHP syntax declares the properties and assigns the constructor values at the same time.

### `?string`

Most properties use the type `string`. The SKU uses `?string`, which means:

```text
string or null
```

Shopify allows a variant to exist without an SKU, so the PHP type should represent that real possibility.

## Creating the Object From an API Response

The class provides a named factory method:

```php
$variant = ShopifyVariantData::fromApiResponse($shopifyResponse);
```

The method translates Shopify's field names into the names used inside the application:

| Shopify response | Data-object property |
|---|---|
| `id` | `graphQlId` |
| `legacyResourceId` | `legacyId` |
| `sku` | `sku` |
| `price` | `price` |

This translation creates a boundary between Shopify's API format and the rest of the application.

If Shopify changes its response format later, the conversion can be updated in one place.

## Required Fields

The GraphQL ID, legacy ID, and price are required. If one is absent or cannot be converted into a string, the factory throws an `InvalidArgumentException`.

For example:

```text
Shopify variant field [price] must be a scalar value.
```

Failing early is safer than creating an incomplete tracezilla SKU later.

The SKU is allowed to be `null` because a Shopify variant can legitimately have no SKU.

## Checking for an SKU

The object provides a method that expresses the question clearly:

```php
if (! $variant->hasSku()) {
    // Skip or report this variant.
}
```

`hasSku()` treats both `null` and an empty or whitespace-only string as missing.

Without that method, this rule might be duplicated throughout commands and services.

## Is This a Laravel Feature?

No. `ShopifyVariantData` is plain PHP.

It does not extend a Laravel class and does not use the database, service container, or HTTP client. That makes it:

- Fast to test.
- Easy to copy into another PHP project.
- Independent of controllers and commands.
- Focused on one responsibility.

It lives inside a Laravel application, but its design does not depend on Laravel.

## How It Is Tested

The unit tests verify that the object:

- Converts a valid Shopify response.
- Allows a missing SKU.
- Detects a missing required field.

Run only these tests with:

```bash
php artisan test tests/Unit/Features/CatalogSynchronization/ShopifyVariantDataTest.php
```

Run the complete suite with:

```bash
php artisan test
```

## The tracezilla SKU Data Object

`ShopifyVariantData` represents data received from Shopify. `TracezillaSkuData` represents the data that will be sent to the tracezilla SKU API.

```php
final readonly class TracezillaSkuData
{
    public function __construct(
        public string $skuCode,
        public string $globalName,
        public float $weightFactorNet,
        public float $weightFactorGross,
        public string $unitOfMeasure,
        public string $lotUnit,
        public float $defaultUomConversion,
    ) {
        // Required string validation.
    }
}
```

The two objects describe opposite sides of the integration:

```text
ShopifyVariantData                     TracezillaSkuData
Data received from Shopify     →       Data sent to tracezilla
Shopify field names                    Application-friendly property names
Variant identity and price             SKU creation fields
```

Keeping both sides explicit prevents Shopify's response format from becoming mixed with tracezilla's request format.

### Creating a tracezilla SKU Object

```php
$sku = new TracezillaSkuData(
    skuCode: 'BANANA-001',
    globalName: 'Organic Banana',
    weightFactorNet: 1.0,
    weightFactorGross: 1.1,
    unitOfMeasure: 'pcs',
    lotUnit: 'colli',
    defaultUomConversion: 1.0,
);
```

The constructor requires every field. Defaults such as `pcs` or `colli` do not belong inside the data object because they are business decisions. The upcoming mapper and catalog configuration will supply those values.

Required string properties cannot be empty or contain only whitespace. Invalid data fails before an API write is attempted.

### Converting to an API Payload

PHP code uses readable camel-case property names such as `skuCode`. The tracezilla API expects snake-case keys such as `sku_code`.

`toApiPayload()` performs that conversion in one place:

```php
$payload = $sku->toApiPayload();
```

The result is:

```php
[
    'sku_code' => 'BANANA-001',
    'global_name' => 'Organic Banana',
    'weight_factor_net' => 1.0,
    'weight_factor_gross' => 1.1,
    'unit_of_measure' => 'pcs',
    'lot_unit' => 'colli',
    'default_uom_conversion' => 1.0,
]
```

This gives the API payload one authoritative definition. It also prevents accidental duplicate keys or slightly different payloads in separate commands.

### Testing `TracezillaSkuData`

Its tests verify:

- Typed property values.
- The exact tracezilla API payload.
- Rejection of a blank SKU code.

Run them with:

```bash
php artisan test tests/Unit/Features/CatalogSynchronization/TracezillaSkuDataTest.php
```

## How the objects are used

`ShopifyVariantData` is created at the Shopify API boundary:

The runtime flow is now:

```text
Shopify GraphQL response node
    ↓
ShopifyVariantData::fromApiResponse()
    ↓
ShopifyCatalogService returns typed objects
    ↓
TracezillaSkusFromShopifyCommand uses named properties
```

For example, `ShopifyCatalogService` performs the conversion at the API boundary:

```php
foreach ($connection['nodes'] as $variant) {
    $productVariants[] = ShopifyVariantData::fromApiResponse($variant);
}
```

The command can then use:

```php
if (! $variant->hasSku()) {
    continue;
}

$sku = $variant->sku;
```

The command no longer needs to know that Shopify originally called the legacy ID field `legacyResourceId` or that the response arrived as an array.

`ShopifyVariantToTracezillaSkuMapper` translates each valid
`ShopifyVariantData` object into `TracezillaSkuData`, and
`TracezillaSkuService` sends its API payload.

Read [Mappers](./mappers.html) for the complete translation rules and customization guide.
