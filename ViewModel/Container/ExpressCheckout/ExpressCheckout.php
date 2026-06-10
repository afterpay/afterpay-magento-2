<?php declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\ExpressCheckout;

use Afterpay\Afterpay\Model\Config;
use Afterpay\Afterpay\Model\Config\Source\ApiMode;
use Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider;
use Afterpay\Afterpay\ViewModel\Container\Container;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

class ExpressCheckout extends Container
{
    public const COUNTRY_CURRENCY_MAP = [
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'USD' => 'US',
        'CAD' => 'CA',
        'GBP' => 'GB'
    ];

    protected Resolver $localeResolver;
    private Session $checkoutSession;

    public function __construct(
        SerializerInterface $serializer,
        Config $config,
        NotAllowedProductsProvider $notAllowedProductsProvider,
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        Session $checkoutSession
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider, $storeManager);
        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'afterpay.express.checkout',
        array $config = []
    ): string {
        if (!$remove && $this->isContainerEnable()) {
            $config['minOrderTotal'] = $this->config->getMinOrderTotal();
            $config['maxOrderTotal'] = $this->config->getMaxOrderTotal();
            $config['countryCode'] = $this->getCountryCode();
            $config['buttonImageUrl'] = $this->getImageurl();
        }

        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }

    public function getCountryCode(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return static::COUNTRY_CURRENCY_MAP[$currencyCode] ?? null;
    }

    public function getImageurl(): string
    {
        $urlPrefix = $this->config->getApiMode() === ApiMode::SANDBOX ? 'static.sandbox' : 'static';
        $localePart = str_replace('_', '-', $this->localeResolver->getLocale());

        return "https://$urlPrefix.afterpay.com/$localePart/integration/button/checkout-with-afterpay/white-on-black.svg";
    }

    public function isRestrictedProductInCart(): bool
    {
        $excludedCategoriesIds = $this->config->getExcludeCategories();
        if (!empty($excludedCategoriesIds)) {
            $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
            foreach ($quoteItems as $item) {
                foreach ($item->getProduct()->getCategoryIds() as $categoryId) {
                    if (in_array($categoryId, $excludedCategoriesIds)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
