<?php

namespace App\Features\ShopifyLocations\Data;

use InvalidArgumentException;

final readonly class ShopifyLocationData
{
    public function __construct(
        public string $graphQlId,
        public string $legacyId,
        public string $name,
        public bool $isActive,
        public bool $hasActiveInventory,
        public bool $fulfillsOnlineOrders,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $country = null,
        public ?string $zip = null,
    ) {
        if (trim($this->graphQlId) === '') {
            throw new InvalidArgumentException('A Shopify location GraphQL ID is required.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('A Shopify location name is required.');
        }
    }

    public static function fromApiResponse(array $location): self
    {
        $address = $location['address'] ?? [];

        if ($address === null) {
            $address = [];
        } elseif (! is_array($address)) {
            throw new InvalidArgumentException('Shopify location address must be an array.');
        }

        return new self(
            graphQlId: self::requiredString($location, 'id'),
            legacyId: self::requiredString($location, 'legacyResourceId'),
            name: self::requiredString($location, 'name'),
            isActive: self::requiredBoolean($location, 'isActive'),
            hasActiveInventory: self::requiredBoolean($location, 'hasActiveInventory'),
            fulfillsOnlineOrders: self::requiredBoolean($location, 'fulfillsOnlineOrders'),
            address1: self::nullableString($address, 'address1'),
            address2: self::nullableString($address, 'address2'),
            city: self::nullableString($address, 'city'),
            province: self::nullableString($address, 'province'),
            country: self::nullableString($address, 'country'),
            zip: self::nullableString($address, 'zip'),
        );
    }

    public function formattedAddress(): string
    {
        $parts = array_filter([
            $this->address1,
            $this->address2,
            trim(implode(' ', array_filter([$this->zip, $this->city]))),
            $this->province,
            $this->country,
        ], static fn (?string $value): bool => $value !== null && trim($value) !== '');

        return implode(', ', $parts);
    }

    public function toArray(): array
    {
        return [
            'graph_ql_id' => $this->graphQlId,
            'legacy_id' => $this->legacyId,
            'name' => $this->name,
            'is_active' => $this->isActive,
            'has_active_inventory' => $this->hasActiveInventory,
            'fulfills_online_orders' => $this->fulfillsOnlineOrders,
            'address' => [
                'address1' => $this->address1,
                'address2' => $this->address2,
                'city' => $this->city,
                'province' => $this->province,
                'country' => $this->country,
                'zip' => $this->zip,
            ],
        ];
    }

    private static function requiredString(array $values, string $key): string
    {
        if (! array_key_exists($key, $values) || ! is_scalar($values[$key])) {
            throw new InvalidArgumentException(
                sprintf('Shopify location field [%s] must be a scalar value.', $key)
            );
        }

        $value = trim((string) $values[$key]);

        if ($value === '') {
            throw new InvalidArgumentException(
                sprintf('Shopify location field [%s] cannot be empty.', $key)
            );
        }

        return $value;
    }

    private static function requiredBoolean(array $values, string $key): bool
    {
        if (! array_key_exists($key, $values) || ! is_bool($values[$key])) {
            throw new InvalidArgumentException(
                sprintf('Shopify location field [%s] must be a boolean.', $key)
            );
        }

        return $values[$key];
    }

    private static function nullableString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException(
                sprintf('Shopify location address field [%s] must be a scalar value or null.', $key)
            );
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
