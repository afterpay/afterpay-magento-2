<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Checks;

class IsCBTAvailable implements \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface
{
    private $config;

    private $canUseCurrentCurrency = null;

    public function __construct(\Afterpay\Afterpay\Model\Config $config)
    {
        $this->config = $config;
    }

    public function check(string $currencyCode, float $amount = null): bool
    {
        $cbtCurrencies = $this->config->getCbtCurrencyLimits();

        if ($amount === null) {
            return key_exists($currencyCode, $cbtCurrencies);
        }

        return key_exists($currencyCode, $cbtCurrencies) && $cbtCurrencies[$currencyCode] > $amount;
    }

    public function checkByQuote(\Magento\Quote\Model\Quote $quote): bool
    {
        if ($this->canUseCurrentCurrency !== null) {
            return $this->canUseCurrentCurrency;
        }

        $currentCurrencyCode = $quote->getQuoteCurrencyCode();
        $amount = (float) $quote->getGrandTotal();
        $this->canUseCurrentCurrency = $this->check($currentCurrencyCode, $amount);

        return $this->canUseCurrentCurrency;
    }
}
