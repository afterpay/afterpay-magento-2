<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\AmountProcessor;

class Order
{
    private $priceRenderer;

    public function __construct(
        \Magento\Weee\Block\Item\Price\Renderer $priceRenderer
    ) {
        $this->priceRenderer = $priceRenderer;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item[] $items
     */
    public function process(array $items, \Magento\Sales\Model\Order\Payment $payment): float
    {
        $amount = 0;
        foreach ($items as $item) {
            if (!$item->getParentItem()) {
                $amount += $this->calculateItemPrice($payment, $item, (float)$item->getQtyOrdered());
            }
        }
        $amount += $this->getShippingAmount($payment->getOrder());

        return $this->processDiscount($amount, $payment);
    }

    protected function calculateItemPrice(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Sales\Model\Order\Item $item,
        float $qty
    ): float {
        $isCBTCurrency = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);
        $rowTotal = $isCBTCurrency ? $this->priceRenderer->getTotalAmount($item) : $this->priceRenderer->getBaseTotalAmount($item);
        $pricePerItem = $rowTotal / $item->getQtyOrdered();

        return $qty * $pricePerItem;
    }

    protected function getShippingAmount(\Magento\Sales\Model\Order $order): float
    {
        if (!$shipmentCollection = $order->getShipmentsCollection()) {
            return 0;
        }
        $isFirstShipping = $shipmentCollection->count() == 0;
        if ($isFirstShipping && $shippingAmount = 1 * $order->getShippingInclTax()) {
            if ($order->getShippingRefunded()) {
                return $shippingAmount - ($order->getShippingRefunded() + $order->getShippingTaxRefunded());
            }
            return $shippingAmount * 1;
        }
        return 0;
    }

    protected function processDiscount(float $amount, \Magento\Payment\Model\InfoInterface $payment): float
    {
        $capturedDiscount = $payment->getAdditionalInformation(
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT
            ) ?? 0;
        $totalDiscountAmount = $payment->getAdditionalInformation(
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT
            ) ?? 0;

        if ($amount >= $totalDiscountAmount) {
            $amount -= $totalDiscountAmount;
            $capturedDiscount += $totalDiscountAmount;
            $totalDiscountAmount = '0.00';
        } else {
            $totalDiscountAmount = $totalDiscountAmount - $amount;
            $capturedDiscount += $amount;
            $amount = 0;
        }

        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT,
            (string)$totalDiscountAmount
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT,
            $capturedDiscount
        );

        return $amount;
    }
}
