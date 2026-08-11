<?php

namespace Tests\Unit\Features\OrderSynchronization;

use App\Features\OrderSynchronization\Data\ShopifyOrderData;
use App\Features\OrderSynchronization\Data\ShopifyOrderLineData;
use App\Features\OrderSynchronization\Data\TracezillaOrderContextData;
use App\Features\OrderSynchronization\Mappers\ShopifyOrderToTracezillaSalesOrderMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ShopifyOrderToTracezillaSalesOrderMapperTest extends TestCase
{
    public function test_it_maps_and_summarizes_an_individual_shopify_order(): void
    {
        $mapped = (new ShopifyOrderToTracezillaSalesOrderMapper)->map(
            $this->order([
                new ShopifyOrderLineData('BAN-001', 2, '10.00', 'DKK'),
                new ShopifyOrderLineData('BAN-001', 1, '7.00', 'DKK'),
                new ShopifyOrderLineData('', 1, '99.00', 'DKK'),
            ]),
            $this->context(),
        );

        $this->assertSame('SHP123', $mapped->externalReference);
        $this->assertSame('from_edi', $mapped->orderHeader['status']);
        $this->assertSame('customer-id', $mapped->orderHeader['partners']['customer']['partner_id']);
        $this->assertSame('warehouse-location-id', $mapped->orderHeader['partners']['pickup_from']['location_id']);
        $this->assertSame('Jane Doe', $mapped->orderHeader['partners']['deliver_to']['location']['recipient_name']);
        $this->assertSame([[
            'sku_code' => 'BAN-001',
            'quantity' => 3,
            'unit_price' => 9.0,
            'price_per' => 'stock_unit',
        ]], $mapped->lines);
    }

    public function test_it_rejects_currency_the_example_does_not_map(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only supports DKK');

        (new ShopifyOrderToTracezillaSalesOrderMapper)->map(
            $this->order(
                [new ShopifyOrderLineData('BAN-001', 1, '10.00', 'EUR')],
                currency: 'EUR',
            ),
            $this->context(),
        );
    }

    private function order(array $lines, string $currency = 'DKK'): ShopifyOrderData
    {
        return new ShopifyOrderData(
            graphQlId: 'gid://shopify/Order/123',
            legacyId: '123',
            name: '#1001',
            createdAt: '2026-07-30T09:00:00Z',
            cancelledAt: null,
            email: 'jane@example.com',
            phone: '+4512345678',
            note: 'Doorbell',
            purchaseOrderNumber: null,
            currency: $currency,
            shippingAddress: [
                'name' => 'Jane Doe',
                'company' => null,
                'address1' => 'Main Street 1',
                'zip' => '1000',
                'city' => 'Copenhagen',
                'countryCodeV2' => 'DK',
            ],
            lines: $lines,
            hasMoreLines: false,
        );
    }

    private function context(): TracezillaOrderContextData
    {
        return new TracezillaOrderContextData(
            'customer-id',
            'customer-location-id',
            7,
            'warehouse-partner-id',
            'warehouse-location-id',
        );
    }
}
