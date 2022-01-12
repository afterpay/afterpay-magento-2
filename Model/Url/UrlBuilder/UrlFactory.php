<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\UrlBuilder;

class UrlFactory
{
    private $config;
    private $storeManager;
    private $environments;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $environments = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->environments = $environments;
    }

    public function create(string $type, ?int $storeId = null): string
    {
        $apiMode = $this->config->getApiMode();
        if (!isset($this->environments[$apiMode][$type]) || !$item = $this->environments[$apiMode][$type]) {
            throw new \InvalidArgumentException('Afterpay environment url config is not found');
        }
        if (is_string($item)) {
            return $item;
        }

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($storeId);
        $currencyCode = $store->getCurrentCurrencyCode();

        if (isset($item[$currencyCode]) && $url = $item[$currencyCode]) {
            return $url;
        }
        return $item['default'];
    }
}
