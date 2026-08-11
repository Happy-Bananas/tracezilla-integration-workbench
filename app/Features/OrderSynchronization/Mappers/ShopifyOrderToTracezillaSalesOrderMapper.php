<?php

namespace App\Features\OrderSynchronization\Mappers;

use App\Features\OrderSynchronization\Data\ShopifyOrderData;
use App\Features\OrderSynchronization\Data\TracezillaOrderContextData;
use App\Features\OrderSynchronization\Data\TracezillaSalesOrderData;
use DateTimeImmutable;
use InvalidArgumentException;

final class ShopifyOrderToTracezillaSalesOrderMapper
{
    /*
     * These are example business rules. Change them to fit the customer:
     * this sample assumes that Shopify prices and tracezilla use DKK.
     */
    public const ORDER_REFERENCE_PREFIX = 'SHP';

    public const SUPPORTED_CURRENCY = 'DKK';

    public const EXCHANGE_RATE = 100;

    public function map(
        ShopifyOrderData $order,
        TracezillaOrderContextData $context,
    ): TracezillaSalesOrderData {
        if ($order->currency !== self::SUPPORTED_CURRENCY) {
            throw new InvalidArgumentException(
                'Example mapping only supports '.self::SUPPORTED_CURRENCY."; order uses {$order->currency}."
            );
        }

        if ($order->shippingAddress === null) {
            throw new InvalidArgumentException('Shopify order has no shipping address.');
        }

        $lines = $this->summarizeLines($order);

        if ($lines === []) {
            throw new InvalidArgumentException('Shopify order has no importable SKU lines.');
        }

        $date = (new DateTimeImmutable($order->createdAt))->format('Y-m-d');
        $externalReference = self::ORDER_REFERENCE_PREFIX.$order->legacyId;

        return new TracezillaSalesOrderData(
            externalReference: $externalReference,
            shopifyOrderName: $order->name,
            orderHeader: array_filter([
                'ext_ref' => $externalReference,
                'marking' => $order->purchaseOrderNumber,
                'delivery_notify_cell' => $order->phone,
                'delivery_notify_email' => $order->email,
                'remark' => $order->note,
                'currency' => self::SUPPORTED_CURRENCY,
                'exchange_rate' => self::EXCHANGE_RATE,
                'order_date' => $date,
                'pickup_date' => $date,
                'delivery_date' => $date,
                'owned_by_id' => $context->ownerId,
                'status' => 'from_edi',
                'partners' => [
                    'customer' => [
                        'partner_id' => $context->customerPartnerId,
                        'location_id' => $context->customerLocationId,
                    ],
                    'pickup_from' => [
                        'partner_id' => $context->warehousePartnerId,
                        'location_id' => $context->warehouseLocationId,
                    ],
                    'deliver_to' => [
                        'partner_id' => $context->customerPartnerId,
                        'location' => $this->deliveryLocation($order->shippingAddress),
                    ],
                ],
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
            lines: $lines,
        );
    }

    private function summarizeLines(ShopifyOrderData $order): array
    {
        $summaries = [];

        foreach ($order->lines as $line) {
            if (! $line->isImportable()) {
                continue;
            }

            if ($line->currency !== $order->currency) {
                throw new InvalidArgumentException('Shopify order line currency does not match the order.');
            }

            $key = $line->sku;
            $quantity = $line->quantity;
            $revenue = $quantity * (float) $line->unitPrice;

            $summaries[$key]['quantity'] = ($summaries[$key]['quantity'] ?? 0) + $quantity;
            $summaries[$key]['revenue'] = ($summaries[$key]['revenue'] ?? 0.0) + $revenue;
        }

        return array_values(array_map(
            fn (array $summary, string $sku): array => [
                'sku_code' => $sku,
                'quantity' => $summary['quantity'],
                'unit_price' => round($summary['revenue'] / $summary['quantity'], 4),
                'price_per' => 'stock_unit',
            ],
            $summaries,
            array_keys($summaries),
        ));
    }

    private function deliveryLocation(array $address): array
    {
        $company = trim((string) ($address['company'] ?? ''));
        $name = trim((string) ($address['name'] ?? ''));

        return array_filter([
            'name' => $company !== '' ? $company : $name,
            'recipient_name' => $company !== '' ? $company : $name,
            'address' => $address['address1'] ?? null,
            'address_line_2' => $address['address2'] ?? null,
            'zip' => $address['zip'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['province'] ?? null,
            'state_code' => $address['provinceCode'] ?? null,
            'country' => $address['countryCodeV2'] ?? null,
            'phone' => $address['phone'] ?? null,
            'contact' => $company !== '' ? $name : null,
            'is_person' => $company === '',
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
