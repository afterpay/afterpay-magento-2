<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Checks;

class PaymentMethod implements PaymentMethodInterface
{
    public function isAfterPayMethod($payment): bool
    {
        return $payment->getMethod() == \Afterpay\Afterpay\Gateway\Config\Config::CODE;
    }
}
