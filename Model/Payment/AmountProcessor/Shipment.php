<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\AmountProcessor;

class Shipment extends \Afterpay\Afterpay\Model\Payment\AmountProcessor\Order
{
    /**
     * @param \Magento\Sales\Model\Order\Shipment\Item[] $items
     */
    public function process(array $items, \Magento\Sales\Model\Order\Payment $payment): float
    {
        $amount = 0;
        foreach ($items as $item) {
            if (!$item->getOrderItem()->getParentItem()) {
                $amount += $this->calculateItemPrice($item->getOrderItem(), (float)$item->getQty());
            }
        }
        $amount += $this->getShippingAmount($payment->getOrder());

        return $this->processDiscount($amount, $payment);
    }
}
