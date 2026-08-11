<?php

namespace App\Features\InventorySynchronization\Data;

use InvalidArgumentException;

final readonly class TracezillaInventoryData
{
    public function __construct(
        public string $sku,
        public bool $traceable,
        public float $traceableQuantityAvailable,
        public float $nonTraceableQuantityAvailable,
        public float $defaultUomConversion,
        public float $nonTraceableUomConversion,
    ) {
        if (trim($sku) === '') {
            throw new InvalidArgumentException('Tracezilla inventory SKU must not be blank.');
        }
    }

    public static function fromApiResponse(array $record): self
    {
        $sku = $record['sku'] ?? [];
        $skuCode = $record['sku_code'] ?? $sku['sku_code'] ?? null;

        if (! is_scalar($skuCode)) {
            throw new InvalidArgumentException('Tracezilla inventory response is missing an SKU.');
        }

        return new self(
            sku: trim((string) $skuCode),
            traceable: (bool) ($sku['traceable'] ?? false),
            traceableQuantityAvailable: (float) ($record['traceable_quantity_available'] ?? 0),
            nonTraceableQuantityAvailable: (float) ($record['none_traceable_quantity_available'] ?? 0),
            defaultUomConversion: (float) ($sku['default_uom_conversion'] ?? 1),
            nonTraceableUomConversion: (float) ($sku['none_traceable_uom_conversion'] ?? 1),
        );
    }
}
