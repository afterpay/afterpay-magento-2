<?php

namespace Afterpay\Afterpay\Model\Checks;

interface PaymentMethodInterface
{
    public function isAfterPayMethod(\Magento\Sales\Model\Order\Payment $payment): bool;
}
