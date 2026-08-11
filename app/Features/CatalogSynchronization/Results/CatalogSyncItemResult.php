<?php

namespace App\Features\CatalogSynchronization\Results;

use InvalidArgumentException;

final readonly class CatalogSyncItemResult
{
    public function __construct(
        public string $sourceId,
        public ?string $sku,
        public CatalogSyncStatus $status,
        public CatalogSyncReason $reason,
        public ?string $message = null,
        public array $details = [],
    ) {
        if (trim($sourceId) === '') {
            throw new InvalidArgumentException(
                'Catalog synchronization item source ID must not be blank.'
            );
        }
    }

    public function toArray(): array
    {
        return [
            'source_id' => $this->sourceId,
            'sku' => $this->sku,
            'status' => $this->status->value,
            'reason' => $this->reason->value,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
