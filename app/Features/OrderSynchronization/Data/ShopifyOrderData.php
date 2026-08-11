<?php

namespace App\Features\OrderSynchronization\Data;

use InvalidArgumentException;

final readonly class ShopifyOrderData
{
    /** @param list<ShopifyOrderLineData> $lines */
    public function __construct(
        public string $graphQlId,
        public string $legacyId,
        public string $name,
        public string $createdAt,
        public ?string $cancelledAt,
        public ?string $email,
        public ?string $phone,
        public ?string $note,
        public ?string $purchaseOrderNumber,
        public string $currency,
        public ?array $shippingAddress,
        public array $lines,
        public bool $hasMoreLines,
    ) {}

    public static function fromApiResponse(array $order): self
    {
        foreach (['id', 'legacyResourceId', 'name', 'createdAt', 'currencyCode'] as $key) {
            if (! isset($order[$key]) || ! is_scalar($order[$key]) || (string) $order[$key] === '') {
                throw new InvalidArgumentException("Shopify order field [{$key}] is required.");
            }
        }

        return new self(
            graphQlId: (string) $order['id'],
            legacyId: (string) $order['legacyResourceId'],
            name: (string) $order['name'],
            createdAt: (string) $order['createdAt'],
            cancelledAt: self::nullableString($order['cancelledAt'] ?? null),
            email: self::nullableString($order['email'] ?? null),
            phone: self::nullableString($order['phone'] ?? null),
            note: self::nullableString($order['note'] ?? null),
            purchaseOrderNumber: self::nullableString($order['poNumber'] ?? null),
            currency: (string) $order['currencyCode'],
            shippingAddress: is_array($order['shippingAddress'] ?? null)
                ? $order['shippingAddress']
                : null,
            lines: array_map(
                fn (array $line): ShopifyOrderLineData => ShopifyOrderLineData::fromApiResponse($line),
                $order['lineItems']['nodes'] ?? [],
            ),
            hasMoreLines: (bool) ($order['lineItems']['pageInfo']['hasNextPage'] ?? false),
        );
    }

    public function isCancelled(): bool
    {
        return $this->cancelledAt !== null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
