<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container;

class Lib implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected $config;
    private $libUrlProvider;
    private $containerConfigPath;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\Url\Lib\LibUrlProvider $libUrlProvider,
        ?string $containerConfigPath = null
    ) {
        $this->config = $config;
        $this->libUrlProvider = $libUrlProvider;
        $this->containerConfigPath = $containerConfigPath;
    }

    public function isContainerEnable(): bool
    {
        $isContainerConfigPathEnabled = true;
        if ($this->containerConfigPath !== null) {
            $isContainerConfigPathEnabled = (bool)$this->config->getByConfigPath($this->containerConfigPath);
        }
        return $isContainerConfigPathEnabled &&
            $this->config->getIsPaymentActive() &&
            $this->config->getMinOrderTotal() !== null &&
            $this->config->getMaxOrderTotal() !== null &&
            in_array($this->config->getMerchantCountry(), $this->config->getSpecificCountries());
    }

    public function getIsLibLoadedAlready(): bool
    {
        return $this->libUrlProvider->getIsLibGotten();
    }

    public function getAfterpayLib(): ?string
    {
        return $this->libUrlProvider->getAfterpayLib();
    }
}
