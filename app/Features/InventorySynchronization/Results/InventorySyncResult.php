<?php

namespace App\Features\InventorySynchronization\Results;

final class InventorySyncResult
{
    /** @var list<InventorySyncItemResult> */
    private array $items = [];

    public function add(InventorySyncItemResult $item): void
    {
        $this->items[] = $item;
    }

    /** @return list<InventorySyncItemResult> */
    public function items(): array
    {
        return $this->items;
    }

    public function count(InventorySyncStatus $status): int
    {
        return count(array_filter(
            $this->items,
            fn (InventorySyncItemResult $item): bool => $item->status === $status,
        ));
    }

    public function hasFailures(): bool
    {
        return $this->count(InventorySyncStatus::Failed) > 0;
    }

    public function summary(): array
    {
        return [
            'updated' => $this->count(InventorySyncStatus::Updated),
            'would_update' => $this->count(InventorySyncStatus::WouldUpdate),
            'unchanged' => $this->count(InventorySyncStatus::Unchanged),
            'skipped' => $this->count(InventorySyncStatus::Skipped),
            'failed' => $this->count(InventorySyncStatus::Failed),
        ];
    }
}
