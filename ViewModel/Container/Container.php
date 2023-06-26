<?php declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container;

class Container implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected const CONTAINERS_LAYOUT_KEYS = [
        'components',
        'children',
        'minicart_content',
        'extra_info'
    ];

    protected $config;
    protected $serializer;
    protected $notAllowedProductsProvider;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->serializer = $serializer;
        $this->config = $config;
        $this->notAllowedProductsProvider = $notAllowedProductsProvider;
        $this->storeManager = $storeManager;
    }

    public function isContainerEnable(): bool
    {
        return $this->config->getIsPaymentActive() &&
            $this->config->getMinOrderTotal() !== null &&
            $this->config->getMaxOrderTotal() !== null &&
            in_array($this->config->getMerchantCountry(), $this->config->getSpecificCountries()) &&
            $this->isCurrentCurrencyAvailable();
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'afterpay.container',
        array $config = []
    ): string {
        /** @var array $jsLayout */
        $jsLayout = $this->serializer->unserialize($jsLayoutJson);
        $updatedJsLayout = $this->updateContainer($jsLayout, $remove, $containerNodeName, $config);
        $updatedJsLayout = $this->serializer->serialize($updatedJsLayout);
        return is_string($updatedJsLayout) ? $updatedJsLayout : $jsLayoutJson;
    }

    protected function updateContainer(array $jsLayout, bool $remove, string $containerNodeName, array $config): array
    {
        if (isset($jsLayout[$containerNodeName])) {
            if ($remove) {
                unset($jsLayout[$containerNodeName]);
                return $jsLayout;
            }
            if (!isset($jsLayout[$containerNodeName]['config'])) {
                $jsLayout[$containerNodeName]['config'] = [];
            }
            foreach ($config as $key => $value) {
                $jsLayout[$containerNodeName]['config'][$key] = $value;
            }
            $jsLayout[$containerNodeName]['config']['notAllowedProducts'] = $this->notAllowedProductsProvider
                ->provideIds();
            return $jsLayout;
        }

        foreach (self::CONTAINERS_LAYOUT_KEYS as $containerLayoutKey) {
            if (isset($jsLayout[$containerLayoutKey])) {
                $jsLayout[$containerLayoutKey] = $this->updateContainer(
                    $jsLayout[$containerLayoutKey],
                    $remove,
                    $containerNodeName,
                    $config
                );
                break;
            }
        }
        return $jsLayout;
    }

    private function isCurrentCurrencyAvailable(): bool
    {
        $currentCurrencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();
        $allowedCurrencies = $this->config->getAllowedCurrencies();
        $validCurrencies = array_keys($this->config->getCbtCurrencyLimits());

        if (in_array($baseCurrencyCode, $allowedCurrencies)) {
            $validCurrencies[] = $baseCurrencyCode;
        }

        return in_array($currentCurrencyCode, $validCurrencies);
    }
}
