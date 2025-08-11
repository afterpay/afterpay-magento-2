<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\ExpressCheckout;

use Afterpay\Afterpay\Model\Config;
use Afterpay\Afterpay\Model\Config\Source\ApiMode;
use Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\Resolver;

class Headless extends ExpressCheckout
{
    private Session $checkoutSession;

    private Registry $registry;

    public function __construct(
        SerializerInterface        $serializer,
        Config                     $config,
        NotAllowedProductsProvider $notAllowedProductsProvider,
        StoreManagerInterface      $storeManager,
        Resolver                   $localeResolver,
        Session                    $checkoutSession,
        Registry                   $registry
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider, $storeManager,$localeResolver);
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
    }

    public function getProductSku(): string
    {
        return $this->registry->registry('current_product')->getSku();
    }

    public function getStoreId(): string
    {
        return (string)$this->storeManager->getStore()->getId();
    }

    public function getCartId(): string
    {
        return (string)$this->checkoutSession->getQuoteId();
    }

    public function getImageurl(): string
    {
        $urlPrefix = $this->config->getApiMode() === ApiMode::SANDBOX ? 'static.sandbox' : 'static';
        $localePart = str_replace('_', '-', $this->localeResolver->getLocale());

        return "https://$urlPrefix.afterpay.com/$localePart/integration/button/checkout-with-afterpay/white-on-black.svg";
    }
}
