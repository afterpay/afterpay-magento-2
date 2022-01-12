<?php declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\Cta;

use Magento\Store\Model\Store;

class Cta extends \Afterpay\Afterpay\ViewModel\Container\Container
{
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private \Magento\Framework\Locale\Resolver $localeResolver;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider);
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'afterpay.cta',
        array $config = []
    ): string {
        if (!$remove && $this->isContainerEnable()) {
            $store = $this->storeManager->getStore();
            $config['dataCurrency'] = $store->getCurrentCurrencyCode();
            $config['dataLocale'] = $this->localeResolver->getLocale();
            $config['dataShowLowerLimit'] = $this->config->getMinOrderTotal() >= 1;
            $config['dataCbtEnabled'] = count($this->config->getSpecificCountries()) > 1;
        }
        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }
}
