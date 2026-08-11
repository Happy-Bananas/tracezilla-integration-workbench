<?php

namespace App\Services;

use App\Clients\TracezillaClient;
use App\Features\CatalogSynchronization\Data\TracezillaSkuData;
use UnexpectedValueException;

class TracezillaSkuService
{
    public function __construct(
        protected TracezillaClient $client,
    ) {}

    public function getSkuCodes(): array
    {
        $skuCodes = [];
        $query = [
            'sortBy' => 'sku_code',
            'sortDirection' => 'asc',
            'perPage' => 250,
        ];
        $visitedPages = [];

        do {
            $response = $this->client
                ->http()
                ->get('/skus', $query)
                ->throw()
                ->json();

            $skuCodes = array_merge(
                $skuCodes,
                collect($response['data'])
                    ->pluck('sku_code')
                    ->filter()
                    ->all(),
            );

            $nextPageUrl = $response['links']['next_page'] ?? null;

            if (empty($nextPageUrl)) {
                break;
            }

            $query = array_merge(
                $query,
                $this->paginationQuery($nextPageUrl),
            );
            $pageFingerprint = http_build_query($query);

            if (isset($visitedPages[$pageFingerprint])) {
                throw new UnexpectedValueException(
                    'tracezilla SKU pagination returned the same next page more than once.'
                );
            }

            $visitedPages[$pageFingerprint] = true;
        } while (true);

        return array_values(array_unique($skuCodes));
    }

    public function createSku(TracezillaSkuData $sku): array
    {
        return $this->client
            ->http()
            ->post('/skus', $sku->toApiPayload())
            ->throw()
            ->json();
    }

    public function listSkus(int $limit = 10): array
    {
        $response = $this->client
            ->http()
            ->get('/skus', [
                'sortBy' => 'sku_code',
                'sortDirection' => 'asc',
                'perPage' => $limit,
            ])
            ->throw()
            ->json();

        return $response['data'];
    }

    private function paginationQuery(string $nextPageUrl): array
    {
        $queryString = parse_url($nextPageUrl, PHP_URL_QUERY);

        if (! is_string($queryString) || $queryString === '') {
            throw new UnexpectedValueException(
                'tracezilla SKU pagination returned an invalid next-page URL.'
            );
        }

        parse_str($queryString, $query);

        if ($query === []) {
            throw new UnexpectedValueException(
                'tracezilla SKU pagination returned no next-page parameters.'
            );
        }

        return $query;
    }
}
