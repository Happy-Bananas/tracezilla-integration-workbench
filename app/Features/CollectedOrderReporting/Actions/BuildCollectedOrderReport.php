<?php

namespace App\Features\CollectedOrderReporting\Actions;

use App\Features\CollectedOrderReporting\Options\CollectedOrderReportOptions;
use App\Features\CollectedOrderReporting\Results\CollectedOrderReportResult;
use App\Services\ShopifyOrderService;
use DateTimeImmutable;
use DateTimeZone;

final readonly class BuildCollectedOrderReport
{
    public function __construct(private ShopifyOrderService $shopify) {}

    public function run(CollectedOrderReportOptions $options): CollectedOrderReportResult
    {
        $zone = new DateTimeZone($options->timezone);
        $since = (new DateTimeImmutable('now', $zone))->modify("-{$options->days} days");
        $orders = $this->shopify->getOrdersCreatedSince($since);
        $selected = $options->limit === null ? $orders : array_slice($orders, 0, $options->limit);
        $totals = [];
        $skippedOrders = 0;
        $skippedLines = 0;

        foreach ($selected as $order) {
            if ($order->isCancelled() || $order->hasMoreLines) {
                $skippedOrders++;

                continue;
            }
            $date = (new DateTimeImmutable($order->createdAt))->setTimezone($zone)->format('Y-m-d');
            foreach ($order->lines as $line) {
                if (! $line->isImportable()) {
                    $skippedLines++;

                    continue;
                }
                $key = implode('|', [$date, $line->currency, $line->sku]);
                $totals[$key] ??= ['date' => $date, 'currency' => $line->currency, 'sku' => $line->sku, 'quantity' => 0, 'revenue_minor' => 0];
                $totals[$key]['quantity'] += $line->quantity;
                $totals[$key]['revenue_minor'] += (int) round(((float) $line->unitPrice) * $line->quantity * 100);
            }
        }

        ksort($totals);
        $lines = array_map(static fn (array $line): array => [
            'date' => $line['date'],
            'currency' => $line['currency'],
            'sku' => $line['sku'],
            'quantity' => $line['quantity'],
            'revenue' => number_format($line['revenue_minor'] / 100, 2, '.', ''),
        ], array_values($totals));

        return new CollectedOrderReportResult($lines, count($orders), count($selected), $skippedOrders, $skippedLines);
    }
}
