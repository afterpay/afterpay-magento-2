<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Cron;

class MerchantConfigurationUpdater
{
    private \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private \Afterpay\Afterpay\Model\Config $config;
    private \Psr\Log\LoggerInterface $logger;
    private \Magento\Framework\App\Cache\TypeListInterface $typeList;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Framework\App\Cache\TypeListInterface $typeList,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->typeList = $typeList;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $websites = $this->storeManager->getWebsites(true);
        foreach ($websites as $website) {
            $websiteId = (int)$website->getId();
            if ($this->config->getIsPaymentActive($websiteId)) {
                try {
                    $this->merchantConfigurationCommand->execute([
                        'websiteId' => $websiteId
                    ]);
                    $this->typeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                }
            }
        }
    }
}
