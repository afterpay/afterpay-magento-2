<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Gateway\Validator;

class StockItemsValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testMultisourceShipmentWithoutSourceDoesNotCreateDeductionRequest(): void
    {
        $shipment = $this->createStub(\Magento\Sales\Model\Order\Shipment::class);
        $singleSourceMode = $this->createStub(\Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface::class);
        $singleSourceMode->method('execute')->willReturn(false);
        $defaultSource = $this->createMock(\Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface::class);
        $defaultSource->expects($this->never())->method('getCode');
        $itemsProvider = $this->createMock(\Magento\InventoryShipping\Model\GetItemsToDeductFromShipment::class);
        $itemsProvider->expects($this->once())->method('execute')->with($shipment)->willReturn(['item']);
        $requestFactory = $this->createMock(
            \Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory::class
        );
        $requestFactory->expects($this->never())->method('execute');
        $sourceValidator = $this->createMock(\Afterpay\Afterpay\Model\Spi\SourceValidatorServiceInterface::class);
        $sourceValidator->expects($this->never())->method('execute');

        $validator = new \Afterpay\Afterpay\Gateway\Validator\StockItemsValidator(
            $singleSourceMode,
            $defaultSource,
            $itemsProvider,
            $requestFactory,
            $sourceValidator
        );
        $validator->validate($shipment);
    }
}
