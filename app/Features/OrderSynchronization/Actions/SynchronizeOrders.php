<?php

namespace App\Features\OrderSynchronization\Actions;

use App\Features\OrderSynchronization\Mappers\ShopifyOrderToTracezillaSalesOrderMapper;
use App\Features\OrderSynchronization\Options\OrderSyncOptions;
use App\Features\OrderSynchronization\Results\OrderSyncItemResult;
use App\Features\OrderSynchronization\Results\OrderSyncResult;
use App\Features\OrderSynchronization\Results\OrderSyncStatus;
use App\Services\ShopifyOrderService;
use App\Services\TracezillaSalesOrderService;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final readonly class SynchronizeOrders
{
    /*
     * Visible example choices. Replace these with the customer's intended
     * webshop partner and warehouse relationship.
     */
    public const TRACEZILLA_CUSTOMER_NAME = 'Banana primary webshop';

    public const TRACEZILLA_WAREHOUSE_LOCATION_NUMBER = 2;

    public function __construct(
        private ShopifyOrderService $shopify,
        private TracezillaSalesOrderService $tracezilla,
        private ShopifyOrderToTracezillaSalesOrderMapper $mapper,
    ) {}

    public function run(OrderSyncOptions $options): OrderSyncResult
    {
        $createdSince = (new DateTimeImmutable('now'))->modify("-{$options->days} days");
        $orders = $this->shopify->getOrdersCreatedSince($createdSince);
        $orders = $options->limit === null ? $orders : array_slice($orders, 0, $options->limit);
        $result = new OrderSyncResult;

        if ($orders === []) {
            return $result;
        }

        $context = $this->tracezilla->getContext(
            self::TRACEZILLA_CUSTOMER_NAME,
            self::TRACEZILLA_WAREHOUSE_LOCATION_NUMBER,
        );
        $existing = $this->tracezilla->getExistingExternalReferences(
            ShopifyOrderToTracezillaSalesOrderMapper::ORDER_REFERENCE_PREFIX,
        );

        foreach ($orders as $order) {
            if ($order->isCancelled()) {
                $result->add(new OrderSyncItemResult(
                    $order->name,
                    OrderSyncStatus::Skipped,
                    'The Shopify order is cancelled.',
                ));

                continue;
            }

            if ($order->hasMoreLines) {
                $result->add(new OrderSyncItemResult(
                    $order->name,
                    OrderSyncStatus::Skipped,
                    'The order has more than 250 lines; add line-item pagination before importing it.',
                ));

                continue;
            }

            try {
                $mapped = $this->mapper->map($order, $context);

                if (isset($existing[$mapped->externalReference])) {
                    $result->add(new OrderSyncItemResult(
                        $order->name,
                        OrderSyncStatus::Skipped,
                        'A tracezilla sales order already has this external reference.',
                        $mapped->externalReference,
                    ));
                } elseif ($options->dryRun) {
                    $result->add(new OrderSyncItemResult(
                        $order->name,
                        OrderSyncStatus::WouldCreate,
                        'Would create one tracezilla sales order.',
                        $mapped->externalReference,
                    ));
                } else {
                    $this->tracezilla->create($mapped);
                    $existing[$mapped->externalReference] = true;
                    $result->add(new OrderSyncItemResult(
                        $order->name,
                        OrderSyncStatus::Created,
                        'Created one tracezilla sales order.',
                        $mapped->externalReference,
                    ));
                }
            } catch (InvalidArgumentException $exception) {
                $result->add(new OrderSyncItemResult(
                    $order->name,
                    OrderSyncStatus::Failed,
                    $exception->getMessage(),
                ));
            } catch (Throwable) {
                $result->add(new OrderSyncItemResult(
                    $order->name,
                    OrderSyncStatus::Failed,
                    'An unexpected error occurred while creating the tracezilla sales order.',
                ));
            }
        }

        return $result;
    }
}
