<?php
namespace Afterpay\Afterpay\Model\Checks;

interface PaymentMethodInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface|\Magento\Quote\Api\Data\PaymentInterface $payment
     *
     * @return bool
     */
    public function isAfterPayMethod($payment): bool;
}
