<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Block\Order;

class Totals
{
    public function afterGetTotals(\Magento\Sales\Block\Order\Totals $subject, $result)
    {
        $order = $subject->getOrder();

        if (!$order) {
            return $result;
        }

        $isCbtCurrency = (bool) $order->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY
        );

        if ($isCbtCurrency === true) {
            unset($result['base_grandtotal']);
        }

        return $result;
    }
}
