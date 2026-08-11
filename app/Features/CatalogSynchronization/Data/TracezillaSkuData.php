<?php

namespace App\Features\CatalogSynchronization\Data;

use InvalidArgumentException;

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
        $this->ensureNotBlank($skuCode, 'skuCode');
        $this->ensureNotBlank($globalName, 'globalName');
        $this->ensureNotBlank($unitOfMeasure, 'unitOfMeasure');
        $this->ensureNotBlank($lotUnit, 'lotUnit');
    }

    public function toApiPayload(): array
    {
        return [
            'sku_code' => $this->skuCode,
            'global_name' => $this->globalName,
            'weight_factor_net' => $this->weightFactorNet,
            'weight_factor_gross' => $this->weightFactorGross,
            'unit_of_measure' => $this->unitOfMeasure,
            'lot_unit' => $this->lotUnit,
            'default_uom_conversion' => $this->defaultUomConversion,
        ];
    }

    private function ensureNotBlank(string $value, string $property): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                "Tracezilla SKU property [{$property}] must not be blank."
            );
        }
    }
}
