<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\StockItemsValidator;

use Magento\Framework\App\ObjectManager;

class StockItemsValidatorProxy implements \Afterpay\Afterpay\Model\Spi\StockItemsValidatorInterface, \Magento\Framework\ObjectManager\NoninterceptableInterface
{
    private $subject = null;
    private $stockItemValidatorFactory;
    private $sourceValidatorServiceFactory;
    private $moduleManager;

    public function __construct(
        \Afterpay\Afterpay\Gateway\Validator\StockItemsValidatorFactory $stockItemsValidatorFactory,
        \Afterpay\Afterpay\Model\SourceValidatorServiceFactory $sourceValidatorServiceFactory,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->stockItemValidatorFactory = $stockItemsValidatorFactory;
        $this->sourceValidatorServiceFactory = $sourceValidatorServiceFactory;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Check msi functionality existing if no then skip validation
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function validate(\Magento\Sales\Model\Order\Shipment $shipment): void
    {
        if (!$this->moduleManager->isEnabled('Magento_InventoryCatalogApi') ||
            !$this->moduleManager->isEnabled('Magento_InventoryShipping') ||
            !$this->moduleManager->isEnabled('Magento_InventorySourceDeductionApi') ||
            !$this->moduleManager->isEnabled('Magento_InventoryConfigurationApi') ||
            !$this->moduleManager->isEnabled('Magento_InventorySalesApi')
        ) {
            return;
        }
        $stockItemsValidator = $this->getStockItemValidator();
        $stockItemsValidator->validate($shipment);
    }

    private function getStockItemValidator(): \Afterpay\Afterpay\Model\Spi\StockItemsValidatorInterface
    {
        if ($this->subject == null) {
            $objectManager = ObjectManager::getInstance();
            $sourceValidatorService = $this->sourceValidatorServiceFactory->create([
               'getSourceItemBySourceCodeAndSku' => $objectManager->create('\\Magento\\InventorySourceDeductionApi\\Model\\GetSourceItemBySourceCodeAndSku'),
               'getStockItemConfiguration' => $objectManager->create('\\Magento\\InventoryConfigurationApi\\Api\\GetStockItemConfigurationInterface'),
               'getStockBySalesChannel' => $objectManager->create('\\Magento\\InventorySalesApi\\Api\\GetStockBySalesChannelInterface'),
            ]);
            $this->subject = $this->stockItemValidatorFactory->create([
                'isSingleSourceMode' => $objectManager->create('\\Magento\\InventoryCatalogApi\\Model\\IsSingleSourceModeInterface'),
                'defaultSourceProvider' => $objectManager->create('\\Magento\\InventoryCatalogApi\\Api\\DefaultSourceProviderInterface'),
                'getItemsToDeductFromShipment' => $objectManager->create('\\Magento\\InventoryShipping\\Model\\GetItemsToDeductFromShipment'),
                'sourceDeductionRequestFromShipmentFactory' => $objectManager->create('\\Magento\\InventoryShipping\\Model\\SourceDeductionRequestFromShipmentFactory'),
                'sourceValidatorService' => $sourceValidatorService,
            ]);
        }
        return $this->subject;
    }
}
