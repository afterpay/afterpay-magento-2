<?php

namespace Afterpay\Afterpay\Plugin\Payment\Checks;

use Magento\Payment\Model\Checks\CanUseForCountry;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class CanUseForCountryPlugin
{
    public function afterIsApplicable(CanUseForCountry $subject, $result, MethodInterface $paymentMethod, Quote $quote)
    {
        if ($paymentMethod->getCode() !== 'afterpay') {
            return $result;
        }

        $billingAddress = $quote->getBillingAddress();
        if ($billingAddress->getCountry()) {
            return $paymentMethod->canUseForCountry($billingAddress->getCountry());
        }

        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress->getCountry()) {
            return $paymentMethod->canUseForCountry($shippingAddress->getCountry());
        }

        return $result;
    }
}
