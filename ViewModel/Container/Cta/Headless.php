<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\Cta;

use Afterpay\Afterpay\Model\Config;
use Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider;
use Afterpay\Afterpay\ViewModel\Container\Container;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Headless extends Container
{
    private Registry $registry;

    private Resolver $localeResolver;

    protected Session $checkoutSession;

    public function __construct(
        SerializerInterface        $serializer,
        Config                     $config,
        NotAllowedProductsProvider $notAllowedProductsProvider,
        StoreManagerInterface      $storeManager,
        Registry                   $registry,
        Resolver                   $localeResolver,
        Session                    $checkoutSession
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider, $storeManager);
        $this->registry = $registry;
        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
    }

    public function getProductSku(): string
    {
        return $this->registry->registry('current_product')->getSku();
    }

    public function getStoreId(): string
    {
        return (string)$this->storeManager->getStore()->getId();
    }

    public function getCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getLocale(): string
    {
        return $this->localeResolver->getLocale();
    }

    public function getCartId(): string
    {
        return (string)$this->checkoutSession->getQuoteId();
    }
}
