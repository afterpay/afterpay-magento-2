<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Checkout\Block\Cart;

class Sidebar
{
    private $ctaContainerViewModel;
    private $config;
    private $expressCheckoutViewModel;

    public function __construct(
        \Afterpay\Afterpay\ViewModel\Container\Cta\Cta $ctaContainerViewModel,
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\ViewModel\Container\ExpressCheckout\ExpressCheckout $expressCheckoutViewModel
    ) {
        $this->ctaContainerViewModel = $ctaContainerViewModel;
        $this->config = $config;
        $this->expressCheckoutViewModel = $expressCheckoutViewModel;
    }

    /**
     * @param string $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJsLayout(\Magento\Checkout\Block\Cart\Sidebar $sidebar, $result): string
    {
        if (is_string($result) &&
            $this->config->getIsPaymentActive() &&
            $this->config->getMinOrderTotal() !== null &&
            $this->config->getMaxOrderTotal() !== null
        ) {
            $result = $this->ctaContainerViewModel->updateJsLayout(
                $result,
                !($this->config->getIsEnableCtaMiniCart() &&
                    $this->ctaContainerViewModel->isContainerEnable())
            );
            $result = $this->expressCheckoutViewModel->updateJsLayout(
                $result,
                !($this->config->getIsEnableExpressCheckoutMiniCart() &&
                    $this->expressCheckoutViewModel->isContainerEnable())
            );
        }
        return $result;
    }
}
