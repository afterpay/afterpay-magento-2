<?php declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\ExpressCheckout;
class ExpressCheckout extends \Afterpay\Afterpay\ViewModel\Container\Container
{
    public const COUNTRY_CURRENCY_MAP = [
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'USD' => 'US',
        'CAD' => 'CA',
        'GBP' => 'GB'
    ];
    protected $localeResolver;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider, $storeManager);
        $this->localeResolver = $localeResolver;
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
            $config['buttonImageUrl'] = 'https://static.afterpay.com/'.str_replace("_","-",$this->localeResolver->getLocale()).'/integration/button/checkout-with-afterpay/white-on-black.svg';

        }
        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }

    public function getCountryCode(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        return static::COUNTRY_CURRENCY_MAP[$currencyCode] ?? null;
    }
}
