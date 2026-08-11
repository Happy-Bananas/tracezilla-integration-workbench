<?php

namespace App\Services;

use App\Clients\TracezillaClient;
use App\Features\OrderSynchronization\Data\TracezillaOrderContextData;
use App\Features\OrderSynchronization\Data\TracezillaSalesOrderData;
use UnexpectedValueException;

class TracezillaSalesOrderService
{
    public function __construct(private TracezillaClient $client) {}

    public function getContext(string $customerName, int $warehouseLocationNumber): TracezillaOrderContextData
    {
        $partners = $this->client->http()
            ->get('/partners', [
                'keyword' => ['ct' => $customerName],
                'role' => ['eq' => 'customer'],
                'include' => 'locations',
                'perPage' => 50,
            ])
            ->throw()
            ->json('data');

        $customer = collect(is_array($partners) ? $partners : [])
            ->first(fn (array $partner): bool => strcasecmp(
                trim((string) ($partner['name'] ?? '')),
                $customerName,
            ) === 0);
        $customerLocation = collect(is_array($customer) ? ($customer['locations'] ?? []) : [])
            ->first(fn (array $location): bool => (bool) ($location['is_primary'] ?? false))
            ?? ($customer['locations'][0] ?? null);

        if (! is_array($customer) || empty($customer['id'])) {
            throw new UnexpectedValueException(
                "Tracezilla customer [{$customerName}] could not be resolved."
            );
        }

        if (! is_array($customerLocation) || empty($customerLocation['id'])) {
            throw new UnexpectedValueException(
                "Tracezilla customer [{$customerName}] has no primary location."
            );
        }

        if (! is_numeric($customer['owned_by_id'] ?? null)) {
            throw new UnexpectedValueException(
                "Tracezilla customer [{$customerName}] has no owner. Assign an owner to the partner before importing orders."
            );
        }

        $warehouse = $this->client->http()
            ->get('/location-by-number/'.$warehouseLocationNumber)
            ->throw()
            ->json('data');

        if (! is_array($warehouse) || empty($warehouse['id']) || empty($warehouse['partner_id'])) {
            throw new UnexpectedValueException(
                "Tracezilla warehouse location [{$warehouseLocationNumber}] could not be resolved."
            );
        }

        return new TracezillaOrderContextData(
            customerPartnerId: (string) $customer['id'],
            customerLocationId: (string) $customerLocation['id'],
            ownerId: (int) $customer['owned_by_id'],
            warehousePartnerId: (string) $warehouse['partner_id'],
            warehouseLocationId: (string) $warehouse['id'],
        );
    }

    /** @return array<string, true> */
    public function getExistingExternalReferences(string $prefix): array
    {
        $records = [];
        $query = [
            'ext_ref' => ['ct' => $prefix],
            'perPage' => 250,
        ];
        $visitedPages = [];

        do {
            $response = $this->client->http()
                ->get('/orders/sales', $query)
                ->throw()
                ->json();
            $records = array_merge($records, $response['data'] ?? []);
            $nextPageUrl = $response['links']['next_page'] ?? null;

            if (empty($nextPageUrl)) {
                break;
            }

            $queryString = parse_url($nextPageUrl, PHP_URL_QUERY);
            parse_str(is_string($queryString) ? $queryString : '', $nextPageQuery);

            if ($nextPageQuery === []) {
                throw new UnexpectedValueException(
                    'Tracezilla sales-order pagination returned an invalid next-page URL.'
                );
            }

            $query = array_merge($query, $nextPageQuery);
            $fingerprint = http_build_query($query);

            if (isset($visitedPages[$fingerprint])) {
                throw new UnexpectedValueException(
                    'Tracezilla sales-order pagination returned the same next page more than once.'
                );
            }

            $visitedPages[$fingerprint] = true;
        } while (true);

        $references = collect($records)
            ->pluck('ext_ref')
            ->filter(fn (mixed $reference): bool => is_string($reference) && $reference !== '')
            ->mapWithKeys(fn (string $reference): array => [$reference => true])
            ->all();

        return $references;
    }

    public function create(TracezillaSalesOrderData $order): array
    {
        return $this->client->http()
            ->put('/orders/sales', $order->toApiPayload())
            ->throw()
            ->json();
    }
}
