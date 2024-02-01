<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\Cta;

use Afterpay\Afterpay\Model\Config;
use Afterpay\Afterpay\Model\Url\Lib\LibUrlProvider;
use Magento\Store\Model\StoreManagerInterface;

class Lib extends \Afterpay\Afterpay\ViewModel\Container\Lib
{
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        Config                $config,
        LibUrlProvider        $libUrlProvider,
        ?string               $containerConfigPath = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($config, $libUrlProvider, $containerConfigPath);
    }

    public function getMinTotalValue(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $cbtLimits = $this->config->getCbtCurrencyLimits();
        if (isset($cbtLimits[$currencyCode])) {
            return $cbtLimits[$currencyCode]['minimumAmount']['amount'] ?? '0';
        }

        return $this->config->getMinOrderTotal();
    }

    public function getMaxTotalValue(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $cbtLimits = $this->config->getCbtCurrencyLimits();
        if (isset($cbtLimits[$currencyCode])) {
            return $cbtLimits[$currencyCode]['maximumAmount']['amount'];
        }

        return $this->config->getMaxOrderTotal();
    }
}
