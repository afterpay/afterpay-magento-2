<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order;

use Magento\Sales\Model\Order\Item;

class OrderItemProvider
{
    const ITEM_SHIPPED = 'shipped';
    const ITEM_REFUNDED = 'refunded';

    private \Afterpay\Afterpay\Model\Order\OrderItemInterfaceFactory $orderItemFactory;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\OrderItemInterfaceFactory $orderItemFactory
    ) {
        $this->orderItemFactory = $orderItemFactory;
    }

    public function provide(Item $orderItem): \Afterpay\Afterpay\Model\Order\OrderItemInterface
    {
        $events = $this->getOrderEvents($orderItem->getOrder());
        $orderItemHistory = $this->getItemHistoryByEvents($orderItem, $events);
        $result = $this->getRefundedVoidedQty($orderItemHistory);

        $orderItem = $this->orderItemFactory->create()
            ->setAfterpayRefundedQty($result['refunded_qty'])
            ->setAfterpayVoidedQty($result['voided_qty']);

        return $orderItem;
    }

    private function getItemHistoryByEvents(Item $orderItem, array $events): array
    {
        $orderItemHistory = [];
        /** @var \Magento\Sales\Model\Order\Shipment|\Magento\Sales\Model\Order\Creditmemo $event */
        foreach ($events as $event) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item|\Magento\Sales\Model\Order\Creditmemo\Item $item */
            foreach ($event->getAllItems() as $item) {
                if ($item->getOrderItemId() == $orderItem->getId()) {
                    $orderItemHistory[] = $item instanceof \Magento\Sales\Api\Data\ShipmentItemInterface
                        ? ['action' => self::ITEM_SHIPPED, 'qty' => $item->getQty()]
                        : ['action' => self::ITEM_REFUNDED, 'qty' => $item->getQty()];
                }
            }
        }
        return $orderItemHistory;
    }

    private function getOrderEvents(\Magento\Sales\Model\Order $order): array
    {
        $events = [];

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($order->getShipmentsCollection() as $shipment) {
            $events[$shipment->getCreatedAt()] = $shipment;
        }
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            $events[$creditmemo->getCreatedAt()] = $creditmemo;
        }

        ksort($events);

        return $events;
    }

    private function getRefundedVoidedQty(array $orderItemHistory): array
    {
        $capturedQty = 0;
        $refundedQty = 0;
        $voidedQty = 0;
        foreach ($orderItemHistory as $status) {
            if ($status['action'] == self::ITEM_REFUNDED) {
                if ($capturedQty > 0) {
                    if ($capturedQty >= $status['qty']) {
                        $capturedQty -= $status['qty'];
                        $refundedQty += $status['qty'];
                    } else {
                        $refundedQty += $capturedQty;
                        $voidedQty += $status['qty'] - $capturedQty;
                        $capturedQty = 0;
                    }
                } else {
                    $voidedQty += $status['qty'];
                }
            } elseif ($status['action'] == self::ITEM_SHIPPED) {
                $capturedQty += $status['qty'];
            }
        }

        return ['refunded_qty' => $refundedQty, 'voided_qty' => $voidedQty];
    }
}
