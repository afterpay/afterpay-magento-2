<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Validator;

class StockItemsValidator implements \Afterpay\Afterpay\Model\Spi\StockItemsValidatorInterface
{
    private \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $isSingleSourceMode;
    private \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface $sourceValidatorService;
    private \Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory;
    private \Magento\InventoryShipping\Model\GetItemsToDeductFromShipment $getItemsToDeductFromShipment;
    private \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface $defaultSourceProvider;

    public function __construct(
        \Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface $isSingleSourceMode,
        \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface $defaultSourceProvider,
        \Magento\InventoryShipping\Model\GetItemsToDeductFromShipment $getItemsToDeductFromShipment,
        \Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory,
        \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface $sourceValidatorService
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->sourceDeductionRequestFromShipmentFactory = $sourceDeductionRequestFromShipmentFactory;
        $this->sourceValidatorService = $sourceValidatorService;
    }

    /**
     * @inheridoc
     */
    public function validate(\Magento\Sales\Model\Order\Shipment $shipment): void
    {
        if ($shipment->getOrigData('entity_id')) {
            return;
        }

        if (!empty($shipment->getExtensionAttributes())
            && !empty($shipment->getExtensionAttributes()->getSourceCode())) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        } elseif ($this->isSingleSourceMode->execute()) {
            $sourceCode = $this->defaultSourceProvider->getCode();
        }

        $shipmentItems = $this->getItemsToDeductFromShipment->execute($shipment);

        if (!empty($shipmentItems)) {
            $sourceDeductionRequest = $this->sourceDeductionRequestFromShipmentFactory->execute(
                $shipment,
                $sourceCode,
                $shipmentItems
            );
            $this->sourceValidatorService->execute($sourceDeductionRequest);
        }
    }
}
