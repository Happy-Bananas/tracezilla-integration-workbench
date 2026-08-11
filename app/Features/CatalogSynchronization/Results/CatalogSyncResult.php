<?php

namespace App\Features\CatalogSynchronization\Results;

final class CatalogSyncResult
{
    /**
     * @var list<CatalogSyncItemResult>
     */
    private array $items = [];

    public function __construct(
        public readonly int $sourceCount,
        public readonly int $existingSkuCount,
        public readonly int $selectedCount,
        public readonly bool $dryRun,
        public readonly ?int $limit,
    ) {}

    public function add(CatalogSyncItemResult $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return list<CatalogSyncItemResult>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return list<CatalogSyncItemResult>
     */
    public function itemsWithStatus(CatalogSyncStatus $status): array
    {
        return array_values(array_filter(
            $this->items,
            fn (CatalogSyncItemResult $item): bool => $item->status === $status,
        ));
    }

    public function count(CatalogSyncStatus $status): int
    {
        return count($this->itemsWithStatus($status));
    }

    public function hasFailures(): bool
    {
        return $this->count(CatalogSyncStatus::Failed) > 0;
    }

    public function summary(): array
    {
        return [
            'source_count' => $this->sourceCount,
            'existing_sku_count' => $this->existingSkuCount,
            'selected_count' => $this->selectedCount,
            'processed_count' => count($this->items),
            'created_count' => $this->count(CatalogSyncStatus::Created),
            'would_create_count' => $this->count(CatalogSyncStatus::WouldCreate),
            'skipped_count' => $this->count(CatalogSyncStatus::Skipped),
            'invalid_count' => $this->count(CatalogSyncStatus::Invalid),
            'failed_count' => $this->count(CatalogSyncStatus::Failed),
            'dry_run' => $this->dryRun,
            'limit' => $this->limit,
        ];
    }

    public function toArray(): array
    {
        return [
            'summary' => $this->summary(),
            'items' => array_map(
                fn (CatalogSyncItemResult $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
