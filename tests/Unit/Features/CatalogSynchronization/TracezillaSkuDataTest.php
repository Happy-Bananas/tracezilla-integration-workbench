<?php

namespace Tests\Unit\Features\CatalogSynchronization;

use App\Features\CatalogSynchronization\Data\TracezillaSkuData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TracezillaSkuDataTest extends TestCase
{
    public function test_it_represents_a_tracezilla_sku_with_typed_properties(): void
    {
        $sku = $this->skuData();

        $this->assertSame('BANANA-001', $sku->skuCode);
        $this->assertSame('Organic Banana', $sku->globalName);
        $this->assertSame(1.0, $sku->weightFactorNet);
        $this->assertSame(1.1, $sku->weightFactorGross);
        $this->assertSame('pcs', $sku->unitOfMeasure);
        $this->assertSame('colli', $sku->lotUnit);
        $this->assertSame(1.0, $sku->defaultUomConversion);
    }

    public function test_it_converts_the_typed_data_to_a_tracezilla_api_payload(): void
    {
        $this->assertSame([
            'sku_code' => 'BANANA-001',
            'global_name' => 'Organic Banana',
            'weight_factor_net' => 1.0,
            'weight_factor_gross' => 1.1,
            'unit_of_measure' => 'pcs',
            'lot_unit' => 'colli',
            'default_uom_conversion' => 1.0,
        ], $this->skuData()->toApiPayload());
    }

    public function test_it_rejects_a_blank_sku_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Tracezilla SKU property [skuCode] must not be blank.'
        );

        new TracezillaSkuData(
            skuCode: '  ',
            globalName: 'Organic Banana',
            weightFactorNet: 1.0,
            weightFactorGross: 1.1,
            unitOfMeasure: 'pcs',
            lotUnit: 'colli',
            defaultUomConversion: 1.0,
        );
    }

    private function skuData(): TracezillaSkuData
    {
        return new TracezillaSkuData(
            skuCode: 'BANANA-001',
            globalName: 'Organic Banana',
            weightFactorNet: 1.0,
            weightFactorGross: 1.1,
            unitOfMeasure: 'pcs',
            lotUnit: 'colli',
            defaultUomConversion: 1.0,
        );
    }
}
