<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Resolver;

use Afterpay\Afterpay\Model\Config;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;

class AfterpayConfig implements ResolverInterface
{
    protected Config $config;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        Config                $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field            $field
     * @param ContextInterface $context
     * @param ResolveInfo      $info
     * @param array|null       $value
     * @param array|null       $args
     *
     * @return array|Value
     * @throws \Exception
     */
    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();
        $maxAmount = $this->config->getMaxOrderTotal((int)$websiteId);
        $minAmount = $this->config->getMinOrderTotal((int)$websiteId);
        $allowedCurrencies = $this->config->getAllowedCurrencies((int)$websiteId);
        $cbtCurrencies = array_keys($this->config->getCbtCurrencyLimits());
        $isEnabled = $this->config->getIsPaymentActive((int)$websiteId);
        $isEnabledCtaProductPage = $this->config->getIsEnableCtaProductPage((int)$websiteId);
        $isEnabledCtaMinicart = $this->config->getIsEnableCtaMiniCart((int)$websiteId);
        $isEnabledCtaCheckout = $this->config->getIsEnableCtaCartPage((int)$websiteId);
        $publicId = $this->config->getPublicId((int)$websiteId);
        $apiMode = $this->config->getApiMode((int)$websiteId);

        return [
            'max_amount'              => $maxAmount,
            'min_amount'              => $minAmount,
            'allowed_currencies'      => array_merge($allowedCurrencies, $cbtCurrencies),
            'is_enabled'              => $isEnabled,
            'is_enabled_cta_pdp'      => $isEnabledCtaProductPage,
            'is_enabled_cta_minicart' => $isEnabledCtaMinicart,
            'is_enabled_cta_checkout' => $isEnabledCtaCheckout,
            'api_mode'                => $apiMode,
            'mpid' => $publicId,
        ];
    }
}
