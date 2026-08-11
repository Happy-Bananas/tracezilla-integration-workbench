<?php

namespace Tests\Unit\Features\OrderSynchronization;

use App\Features\OrderSynchronization\Actions\SynchronizeOrders;
use App\Features\OrderSynchronization\Data\ShopifyOrderData;
use App\Features\OrderSynchronization\Data\ShopifyOrderLineData;
use App\Features\OrderSynchronization\Data\TracezillaOrderContextData;
use App\Features\OrderSynchronization\Mappers\ShopifyOrderToTracezillaSalesOrderMapper;
use App\Features\OrderSynchronization\Options\OrderSyncOptions;
use App\Features\OrderSynchronization\Results\OrderSyncStatus;
use App\Services\ShopifyOrderService;
use App\Services\TracezillaSalesOrderService;
use PHPUnit\Framework\TestCase;

class SynchronizeOrdersTest extends TestCase
{
    public function test_dry_run_reports_order_without_writing(): void
    {
        $shopify = $this->createMock(ShopifyOrderService::class);
        $shopify->method('getOrdersCreatedSince')->willReturn([$this->order()]);
        $tracezilla = $this->tracezilla();
        $tracezilla->expects($this->never())->method('create');

        $result = $this->action($shopify, $tracezilla)->run(new OrderSyncOptions);

        $this->assertSame(1, $result->count(OrderSyncStatus::WouldCreate));
        $this->assertSame('SHP123', $result->items()[0]->externalReference);
    }

    public function test_execution_creates_only_a_new_order(): void
    {
        $shopify = $this->createMock(ShopifyOrderService::class);
        $shopify->method('getOrdersCreatedSince')->willReturn([
            $this->order('123', '#1001'),
            $this->order('124', '#1002'),
        ]);
        $tracezilla = $this->tracezilla(['SHP123' => true]);
        $tracezilla->expects($this->once())
            ->method('create')
            ->with($this->callback(fn ($order): bool => $order->externalReference === 'SHP124'));

        $result = $this->action($shopify, $tracezilla)
            ->run(new OrderSyncOptions(dryRun: false));

        $this->assertSame(1, $result->count(OrderSyncStatus::Created));
        $this->assertSame(1, $result->count(OrderSyncStatus::Skipped));
    }

    private function action(
        ShopifyOrderService $shopify,
        TracezillaSalesOrderService $tracezilla,
    ): SynchronizeOrders {
        return new SynchronizeOrders(
            $shopify,
            $tracezilla,
            new ShopifyOrderToTracezillaSalesOrderMapper,
        );
    }

    private function tracezilla(array $existing = []): TracezillaSalesOrderService
    {
        $service = $this->createMock(TracezillaSalesOrderService::class);
        $service->method('getContext')->willReturn(new TracezillaOrderContextData(
            'customer-id',
            'customer-location-id',
            7,
            'warehouse-partner-id',
            'warehouse-location-id',
        ));
        $service->method('getExistingExternalReferences')->willReturn($existing);

        return $service;
    }

    private function order(string $id = '123', string $name = '#1001'): ShopifyOrderData
    {
        return new ShopifyOrderData(
            'gid://shopify/Order/'.$id,
            $id,
            $name,
            '2026-07-30T09:00:00Z',
            null,
            'jane@example.com',
            '+4512345678',
            null,
            null,
            'DKK',
            [
                'name' => 'Jane Doe',
                'address1' => 'Main Street 1',
                'zip' => '1000',
                'city' => 'Copenhagen',
                'countryCodeV2' => 'DK',
            ],
            [new ShopifyOrderLineData('BAN-001', 2, '10.00', 'DKK')],
            false,
        );
    }
}
