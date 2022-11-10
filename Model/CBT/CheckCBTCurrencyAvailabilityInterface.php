<?php

namespace Afterpay\Afterpay\Model\CBT;

interface CheckCBTCurrencyAvailabilityInterface
{
    public function check(string $currencyCode, float $amount = null): bool;

    public function checkByQuote(\Magento\Quote\Model\Quote $quote): bool;
}
