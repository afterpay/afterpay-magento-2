<?php declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\ExpressCheckout;

class ExpressCheckout extends \Afterpay\Afterpay\ViewModel\Container\Container
{
    const COUNTRY_CURRENCY_MAP = [
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'USD' => 'US',
        'CAD' => 'CA',
        'GBP' => 'GB'
    ];

    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider);
        $this->storeManager = $storeManager;
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
        }
        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }

    private function getCountryCode(): ?string
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        return static::COUNTRY_CURRENCY_MAP[$currencyCode] ?? null;
    }
}
