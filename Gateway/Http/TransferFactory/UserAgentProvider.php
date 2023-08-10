<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Http\TransferFactory;

class UserAgentProvider
{
    private $moduleList;
    private $productMetadata;
    private $util;
    private $config;
    private $store;

    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Util $util,
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Store\Model\Store $store
    ) {
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->util = $util;
        $this->config = $config;
        $this->store = $store;
    }

    public function provide(?int $websiteId = null): string
    {
        $afterpayModule = $this->moduleList->getOne('Afterpay_Afterpay');
        $moduleVersion = $afterpayModule['setup_version'] ?? null;
        $magentoProductName = $this->productMetadata->getName();
        $magentoProductEdition = $this->productMetadata->getEdition();
        $magentoVersion = $this->productMetadata->getVersion();
        $phpVersion = $this->util->getTrimmedPhpVersion();
        $afterpayMerchantId = $this->config->getMerchantId($websiteId);
        $afterpayMPId = $this->config->getPublicId($websiteId);
        $websiteDomain = $this->store->getBaseUrl();
        $CashAppPayAvailable=(int)$this->config->getCashAppPayAvailable($websiteId);
        $CashAppPayEnabled=(int)$this->config->getCashAppPayEnabled($websiteId);

        return "AfterpayMagento2Plugin $moduleVersion ($magentoProductName $magentoProductEdition $magentoVersion) " .
            "PHPVersion: PHP/$phpVersion MerchantID: $afterpayMerchantId; MPID/$afterpayMPId; CAPAvailable/$CashAppPayAvailable; CAPEnabled/$CashAppPayEnabled; URL: $websiteDomain";
    }
}
