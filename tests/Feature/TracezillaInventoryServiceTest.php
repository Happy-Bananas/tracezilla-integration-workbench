<?php

namespace Tests\Feature;

use App\Clients\TracezillaClient;
use App\Services\TracezillaInventoryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TracezillaInventoryServiceTest extends TestCase
{
    public function test_it_loads_inventory_for_the_configured_warehouse(): void
    {
        config([
            'services.tracezilla.base_url' => 'https://tracezilla.test',
            'services.tracezilla.team_slug' => 'team',
            'services.tracezilla.api_key' => 'key',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://tracezilla.test/api/v1/team/location-by-number/42' => Http::response([
                'data' => ['id' => 'warehouse-id'],
            ]),
            'https://tracezilla.test/api/v1/team/inventory*' => Http::response([
                'data' => [[
                    'sku_code' => 'BANANA-001',
                    'traceable_quantity_available' => 2,
                    'none_traceable_quantity_available' => 3,
                    'sku' => [
                        'sku_code' => 'BANANA-001',
                        'traceable' => true,
                        'default_uom_conversion' => 6,
                        'none_traceable_uom_conversion' => 2,
                    ],
                ]],
            ]),
        ]);

        $items = (new TracezillaInventoryService(new TracezillaClient))
            ->getWarehouseInventory(42);

        $this->assertCount(1, $items);
        $this->assertSame('BANANA-001', $items[0]->sku);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://tracezilla.test/api/v1/team/inventory?partner_location%5Beq%5D=warehouse-id&include=sku&perPage=250'
        );
    }
}
