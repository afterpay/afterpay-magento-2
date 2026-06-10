<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\ExpressCheckout;

use Afterpay\Afterpay\Model\Config;
use Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Headless extends ExpressCheckout
{
    private Session $checkoutSession;

    private Registry $registry;

    public function __construct(
        SerializerInterface $serializer,
        Config $config,
        NotAllowedProductsProvider $notAllowedProductsProvider,
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        Session $checkoutSession,
        Registry $registry
    ) {
        parent::__construct(
            $serializer,
            $config,
            $notAllowedProductsProvider,
            $storeManager,
            $localeResolver,
            $checkoutSession
        );
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

    public function isHyva(): bool
    {
        if (class_exists(\Hyva\Theme\Service\CurrentTheme::class)) {
            /** @var \Hyva\Theme\Service\CurrentTheme $currentTheme */
            $currentTheme = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Hyva\Theme\Service\CurrentTheme::class);
            return $currentTheme->isHyva();
        }
        return false;
    }
}
