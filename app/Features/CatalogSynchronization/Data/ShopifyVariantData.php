<?php

namespace App\Features\CatalogSynchronization\Data;

use InvalidArgumentException;

final readonly class ShopifyVariantData
{
    public function __construct(
        public string $graphQlId,
        public string $legacyId,
        public ?string $sku,
        public string $price,
    ) {}

    public static function fromApiResponse(array $variant): self
    {
        return new self(
            graphQlId: self::requiredString($variant, 'id'),
            legacyId: self::requiredString($variant, 'legacyResourceId'),
            sku: isset($variant['sku']) ? trim((string) $variant['sku']) : null,
            price: self::requiredString($variant, 'price'),
        );
    }

    public function hasSku(): bool
    {
        return $this->sku !== null && trim($this->sku) !== '';
    }

    private static function requiredString(array $variant, string $key): string
    {
        if (! array_key_exists($key, $variant) || ! is_scalar($variant[$key])) {
            throw new InvalidArgumentException(
                sprintf('Shopify variant field [%s] must be a scalar value.', $key)
            );
        }

        return (string) $variant[$key];
    }
}
