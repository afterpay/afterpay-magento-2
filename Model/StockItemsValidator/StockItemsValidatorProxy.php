<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\StockItemsValidator;

use Afterpay\Afterpay\Gateway\Validator\StockItemsValidatorFactory;
use Afterpay\Afterpay\Model\SourceValidatorServiceFactory;
use Afterpay\Afterpay\Model\Spi\StockItemsValidatorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManager\NoninterceptableInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventoryShipping\Model\GetItemsToDeductFromShipment;
use Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory as SourceDeductionRequestFactory;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\Sales\Model\Order\Shipment;


class StockItemsValidatorProxy implements StockItemsValidatorInterface, NoninterceptableInterface
{
    private $subject = null;
    private $stockItemValidatorFactory;
    private $sourceValidatorServiceFactory;
    private $moduleManager;

    public function __construct(
        StockItemsValidatorFactory    $stockItemsValidatorFactory,
        SourceValidatorServiceFactory $sourceValidatorServiceFactory,
        Manager                       $moduleManager
    ) {
        $this->stockItemValidatorFactory = $stockItemsValidatorFactory;
        $this->sourceValidatorServiceFactory = $sourceValidatorServiceFactory;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Check msi functionality existing if no then skip validation
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SkuIsNotAssignedToStockException
     */
    public function validate(Shipment $shipment): void
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

    private function getStockItemValidator(): StockItemsValidatorInterface
    {
        if ($this->subject == null) {
            $objectManager = ObjectManager::getInstance();
            $sourceValidatorService = $this->sourceValidatorServiceFactory->create([

                'getSourceItemBySourceCodeAndSku' => $objectManager->create(GetSourceItemBySourceCodeAndSku::class),
                'getStockItemConfiguration'       => $objectManager->create(GetStockItemConfigurationInterface::class),
                'getStockBySalesChannel'          => $objectManager->create(GetStockBySalesChannelInterface::class),
            ]);
            $this->subject = $this->stockItemValidatorFactory->create([
                'isSingleSourceMode'                        => $objectManager->create(IsSingleSourceModeInterface::class),
                'defaultSourceProvider'                     => $objectManager->create(DefaultSourceProviderInterface::class),
                'getItemsToDeductFromShipment'              => $objectManager->create(GetItemsToDeductFromShipment::class),
                'sourceDeductionRequestFromShipmentFactory' => $objectManager->create(SourceDeductionRequestFactory::class),
                'sourceValidatorService'                    => $sourceValidatorService,
            ]);
        }

        return $this->subject;
    }
}
