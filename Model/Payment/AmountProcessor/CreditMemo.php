<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\AmountProcessor;

class CreditMemo
{
    private \Afterpay\Afterpay\Model\Order\OrderItemProvider $orderItemProvider;
    private \Magento\Weee\Block\Item\Price\Renderer $priceRenderer;
    private \Afterpay\Afterpay\Model\Config $config;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\OrderItemProvider $orderItemProvider,
        \Magento\Weee\Block\Item\Price\Renderer          $priceRenderer,
        \Afterpay\Afterpay\Model\Config                  $config
    ) {
        $this->orderItemProvider = $orderItemProvider;
        $this->priceRenderer = $priceRenderer;
        $this->config = $config;
    }

    public function process(\Magento\Sales\Model\Order\Payment $payment): array
    {
        $amountToRefund = $amountToVoid = 0;

        if ($this->config->getIsCreditMemoGrandTotalOnlyEnabled((int)$payment->getOrder()->getStore()->getWebsiteId(), true)) {
            $this->processWithGrandTotal($payment, $amountToVoid, $amountToRefund);
        } else {
            $this->processWithSeparateCalculations($payment, $amountToVoid, $amountToRefund);
        }

        return [$amountToRefund, $amountToVoid];
    }

    private function processWithSeparateCalculations(
        \Magento\Sales\Model\Order\Payment $payment,
        float                              &$amountToVoid,
        float                              &$amountToRefund
    ): void {
        $creditmemo = $payment->getCreditmemo();
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();

            if (!$creditmemoItem->getBaseRowTotalInclTax()) {
                continue;
            }

            if ($orderItem->getIsVirtual()) {
                $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
                continue;
            }

            if ($this->getItemCapturedQty($orderItem) <= 0) {
                $amountToVoid += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
                continue;
            }

            $orderItemQtyRefunded = $orderItem->getOrigData('qty_refunded');
            if (!(float)$orderItemQtyRefunded) {
                $this->processForCapturedButNotRefunded($payment, $orderItem, $creditmemoItem, $amountToRefund, $amountToVoid);
                continue;
            }

            $this->processForCapturedAndRefunded($payment, $orderItem, $creditmemoItem, $amountToRefund, $amountToVoid);

        }

        $this->processShipmentAmount($payment, $creditmemo, $amountToRefund, $amountToVoid);
        $this->processCapturedDiscountForRefundAmount($payment, $amountToRefund);
        $this->processRolloverDiscountForVoidAmount($payment, $amountToVoid);
        $this->processAdjustmentAmount($payment, $amountToVoid, $amountToRefund);

    }

    private function processAdjustmentAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        float &$amountToVoid,
        float &$amountToRefund): void
    {
        $additionalInfo = $payment->getAdditionalInformation();
        $paymentState = $additionalInfo[\Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE] ?? '';   // @codingStandardsIgnoreLine
        $creditmemo = $payment->getCreditmemo();

        if ($paymentState === \Afterpay\Afterpay\Model\PaymentStateInterface::AUTH_APPROVED) {
            $amountToVoid += $creditmemo->getAdjustmentPositive();
            $amountToVoid -= $creditmemo->getAdjustmentNegative();
            return;
        }

        if ($paymentState === \Afterpay\Afterpay\Model\PaymentStateInterface::CAPTURED
        || $paymentState === \Afterpay\Afterpay\Model\PaymentStateInterface::PARTIALLY_CAPTURED) {
            $amountToRefund += $creditmemo->getAdjustmentPositive();
            $amountToRefund -= $creditmemo->getAdjustmentNegative();
        }
    }

    private function processForCapturedButNotRefunded(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Sales\Model\Order\Item $orderItem,
        \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem,
        float &$amountToRefund,
        float &$amountToVoid
    ): void {
        $itemCapturedQty = $this->getItemCapturedQty($orderItem);
        if ($itemCapturedQty >= $creditmemoItem->getQty()) {
            $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
        } else {
            $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$itemCapturedQty);
            $amountToVoid += $this->calculateItemPrice(
                $payment,
                $creditmemoItem,
                (float)($creditmemoItem->getQty() - $itemCapturedQty)
            );
        }
    }

    private function processForCapturedAndRefunded(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Sales\Model\Order\Item $orderItem,
        \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem,
        float &$amountToRefund,
        float &$amountToVoid
    ): void {
        $afterpayOrderItemHistory = $this->orderItemProvider->provide($orderItem);
        $itemCapturedQty = $this->getItemCapturedQty($orderItem);
        $allowedToRefundQty = $itemCapturedQty - $afterpayOrderItemHistory->getAfterpayRefundedQty();
        if ($allowedToRefundQty > 0) {
            if ($creditmemoItem->getQty() > $allowedToRefundQty) {
                $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$allowedToRefundQty);
                $amountToVoid += $this->calculateItemPrice(
                    $payment,
                    $creditmemoItem,
                    (float)($creditmemoItem->getQty() - $allowedToRefundQty)
                );
            } else {
                $amountToRefund += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());   // @codingStandardsIgnoreLine
            }
        } else {
            $amountToVoid += $this->calculateItemPrice($payment, $creditmemoItem, (float)$creditmemoItem->getQty());
        }
    }

    private function processShipmentAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        float &$amountToRefund,
        float &$amountToVoid
    ): void {
        $paymentState = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE
        );
        if ($paymentState == \Afterpay\Afterpay\Model\PaymentStateInterface::CAPTURED) {
            $amountToRefund += $creditmemo->getShippingInclTax();
            return;
        }

        if ($payment->getOrder()->getShipmentsCollection()->count()) {
            $amountToRefund += $creditmemo->getShippingInclTax();
        } else {
            $amountToVoid += $creditmemo->getShippingInclTax();
        }
    }

    private function processCapturedDiscountForRefundAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        float &$amountToRefund
    ): void {
        $capturedDiscount = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT
        );
        if ($amountToRefund > 0 && $capturedDiscount > 0) {
            if ($capturedDiscount <= $amountToRefund) {
                $amountToRefund -= $capturedDiscount;
                $capturedDiscount = 0;
            } else {
                $capturedDiscount -= $amountToRefund;
                $amountToRefund = 0;
            }
            $payment->setAdditionalInformation(
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT,
                $capturedDiscount
            );
        }
    }

    private function processRolloverDiscountForVoidAmount(
        \Magento\Sales\Model\Order\Payment $payment,
        float &$amountToVoid
    ): void {
        $rolloverDiscount = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT
        );
        if ($rolloverDiscount > 0 && $amountToVoid > 0) {
            if ($rolloverDiscount <= $amountToVoid) {
                $amountToVoid -= $rolloverDiscount;
                $rolloverDiscount = 0;
            } else {
                $rolloverDiscount -= $amountToVoid;
                $amountToVoid = 0;
            }
            $payment->setAdditionalInformation(
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT,
                $rolloverDiscount
            );
        }
    }

    private function calculateItemPrice(
        \Magento\Sales\Model\Order\Payment $payment,
        \Magento\Sales\Model\Order\Creditmemo\Item $item,
        float $qty
    ): float {
        $isCBTCurrency = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);   // @codingStandardsIgnoreLine
        $rowTotal = $isCBTCurrency ? $this->priceRenderer->getTotalAmount($item) : $this->priceRenderer->getBaseTotalAmount($item);  // @codingStandardsIgnoreLine
        $pricePerItem = $rowTotal / $item->getQty();

        return $qty * $pricePerItem;
    }

    private function getItemCapturedQty(\Magento\Sales\Model\Order\Item $item): float
    {
        $paymentState = $item->getOrder()->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE
        );
        switch ($paymentState) {
            case \Afterpay\Afterpay\Model\PaymentStateInterface::CAPTURED:
                return (float)$item->getQtyOrdered();
            case \Afterpay\Afterpay\Model\PaymentStateInterface::AUTH_APPROVED:
            case \Afterpay\Afterpay\Model\PaymentStateInterface::PARTIALLY_CAPTURED:
                return $item->getParentItem()
                    ? (float)$item->getParentItem()->getQtyShipped()
                    : (float)$item->getQtyShipped();
        }
        return 0;
    }

    private function processWithGrandTotal(
        \Magento\Sales\Model\Order\Payment $payment,
        float                              &$amountToVoid,
        float                              &$amountToRefund
    ): void {
        $isCBTCurrency = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);
        $paymentState = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE);
        $creditmemo = $payment->getCreditmemo();
        $amount = $isCBTCurrency ? $creditmemo->getGrandTotal() : $creditmemo->getBaseGrandTotal();

        switch ($paymentState) {
            case \Afterpay\Afterpay\Model\PaymentStateInterface::AUTH_APPROVED:
                $amountToVoid += $amount;
                break;
            case \Afterpay\Afterpay\Model\PaymentStateInterface::PARTIALLY_CAPTURED:
                $openToCapture = $payment->getAdditionalInformation(
                    \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_OPEN_TO_CAPTURE_AMOUNT
                );
                $orderAmount = $isCBTCurrency ? $payment->getOrder()->getGrandTotal() : $payment->getOrder()->getBaseGrandTotal();

                if ($amount == $orderAmount) {
                    if ($openToCapture && $amount > $openToCapture) {
                        $amountToVoid += $openToCapture;
                        $amountToRefund += $amount - $openToCapture;
                    } else {
                        $amountToRefund += $amount;
                    }
                } else {
                    $this->processWithSeparateCalculations($payment, $amountToVoid, $amountToRefund);
                }
                break;
            case \Afterpay\Afterpay\Model\PaymentStateInterface::CAPTURED:
            default:
                $amountToRefund += $amount;
                break;
        }
    }
}
