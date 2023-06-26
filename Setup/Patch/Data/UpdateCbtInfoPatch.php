<?php

namespace Afterpay\Afterpay\Setup\Patch\Data;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class UpdateCbtInfoPatch implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private $merchantConfigurationCommand;
    private $storeManager;
    private $typeList;
    private $logger;

    public function __construct(
        CommandInterface      $merchantConfigurationCommand,
        StoreManagerInterface $storeManager,
        TypeListInterface     $typeList,
        LoggerInterface       $logger
    ) {
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->storeManager = $storeManager;
        $this->typeList = $typeList;
        $this->logger = $logger;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function apply()
    {
        $websites = $this->storeManager->getWebsites(true);
        foreach ($websites as $website) {
            $websiteId = (int)$website->getId();
            try {
                $this->merchantConfigurationCommand->execute([
                    'websiteId' => $websiteId
                ]);
                $this->typeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return $this;
    }
}
