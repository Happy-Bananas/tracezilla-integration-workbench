<?php

namespace App\Features\ShopifyLocations\Results;

use App\Features\ShopifyLocations\Data\ShopifyLocationData;
use InvalidArgumentException;

final readonly class ShopifyLocationResult
{
    /**
     * @param  list<ShopifyLocationData>  $locations
     */
    public function __construct(
        public array $locations,
    ) {
        foreach ($this->locations as $location) {
            if (! $location instanceof ShopifyLocationData) {
                throw new InvalidArgumentException(
                    'A Shopify location result may only contain ShopifyLocationData objects.'
                );
            }
        }
    }

    public function count(): int
    {
        return count($this->locations);
    }

    public function isEmpty(): bool
    {
        return $this->locations === [];
    }

    public function toArray(): array
    {
        return [
            'count' => $this->count(),
            'locations' => array_map(
                static fn (ShopifyLocationData $location): array => $location->toArray(),
                $this->locations,
            ),
        ];
    }
}
