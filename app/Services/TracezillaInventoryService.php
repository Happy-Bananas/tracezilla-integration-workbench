<?php

namespace App\Services;

use App\Clients\TracezillaClient;
use App\Features\InventorySynchronization\Data\TracezillaInventoryData;
use UnexpectedValueException;

class TracezillaInventoryService
{
    public function __construct(private TracezillaClient $client) {}

    /** @return list<TracezillaInventoryData> */
    public function getWarehouseInventory(int $warehouseLocationNumber): array
    {
        $location = $this->client->http()
            ->get('/location-by-number/'.$warehouseLocationNumber)
            ->throw()
            ->json('data');

        if (! is_array($location) || empty($location['id'])) {
            throw new UnexpectedValueException('Tracezilla warehouse response is missing an ID.');
        }

        $records = [];
        $query = [
            'partner_location' => ['eq' => $location['id']],
            'include' => 'sku',
            'perPage' => 250,
        ];
        $visitedPages = [];

        do {
            $response = $this->client->http()
                ->get('/inventory', $query)
                ->throw()
                ->json();
            $records = array_merge($records, $response['data'] ?? []);
            $nextPageUrl = $response['links']['next_page'] ?? null;

            if (empty($nextPageUrl)) {
                break;
            }

            $query = array_merge($query, $this->paginationQuery($nextPageUrl));
            $fingerprint = http_build_query($query);

            if (isset($visitedPages[$fingerprint])) {
                throw new UnexpectedValueException(
                    'Tracezilla inventory pagination returned the same next page more than once.'
                );
            }

            $visitedPages[$fingerprint] = true;
        } while (true);

        return array_map(
            fn (array $record): TracezillaInventoryData => TracezillaInventoryData::fromApiResponse($record),
            $records,
        );
    }

    private function paginationQuery(string $nextPageUrl): array
    {
        $queryString = parse_url($nextPageUrl, PHP_URL_QUERY);

        if (! is_string($queryString) || $queryString === '') {
            throw new UnexpectedValueException(
                'Tracezilla inventory pagination returned an invalid next-page URL.'
            );
        }

        parse_str($queryString, $query);

        if ($query === []) {
            throw new UnexpectedValueException(
                'Tracezilla inventory pagination returned no next-page parameters.'
            );
        }

        return $query;
    }
}
