<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\AmountProcessor;

class VirtualProducts extends \Afterpay\Afterpay\Model\Payment\AmountProcessor\Order
{
    protected function processDiscount(float $amount, \Magento\Payment\Model\InfoInterface $payment): float
    {
        $capturedDiscount = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT
        ) ?? 0;
        $totalDiscountAmount = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT
        ) ?? 0;

        $isCBTCurrency = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);
        $orderTotal = $isCBTCurrency ? $payment->getOrder()->getGrandTotal() : $payment->getOrder()->getBaseGrandTotal();
        $totalWithoutVirtualProducts = $orderTotal - $amount;
        $returnAmount = $amount;

        if ($amount > $totalDiscountAmount) {
            $returnAmount -= $totalDiscountAmount;
            $capturedDiscount += $totalDiscountAmount;
            $totalDiscountAmount = '0.00';
        } else {
            if ($totalWithoutVirtualProducts < $totalDiscountAmount) {
                $returnAmount = $orderTotal;
            }

            $openToCapture = $payment->getAdditionalInformation(
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_OPEN_TO_CAPTURE_AMOUNT
            );
            if ($openToCapture && $openToCapture == $returnAmount) {
                $capturedDiscount += $totalDiscountAmount;
                $totalDiscountAmount = '0.00';
            }
        }

        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT,
            (string)$totalDiscountAmount
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT,
            $capturedDiscount
        );

        return $returnAmount;
    }
}
