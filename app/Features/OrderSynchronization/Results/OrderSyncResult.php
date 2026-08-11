<?php

namespace App\Features\OrderSynchronization\Results;

final class OrderSyncResult
{
    /** @var list<OrderSyncItemResult> */
    private array $items = [];

    public function add(OrderSyncItemResult $item): void
    {
        $this->items[] = $item;
    }

    /** @return list<OrderSyncItemResult> */
    public function items(): array
    {
        return $this->items;
    }

    public function count(OrderSyncStatus $status): int
    {
        return count(array_filter(
            $this->items,
            fn (OrderSyncItemResult $item): bool => $item->status === $status,
        ));
    }

    public function hasFailures(): bool
    {
        return $this->count(OrderSyncStatus::Failed) > 0;
    }

    public function summary(): array
    {
        return [
            'created' => $this->count(OrderSyncStatus::Created),
            'would_create' => $this->count(OrderSyncStatus::WouldCreate),
            'skipped' => $this->count(OrderSyncStatus::Skipped),
            'failed' => $this->count(OrderSyncStatus::Failed),
        ];
    }
}
