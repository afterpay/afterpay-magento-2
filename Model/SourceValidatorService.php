<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

class SourceValidatorService implements \Afterpay\Afterpay\Model\Spi\SourceValidatorServiceInterface
{
    private $getStockBySalesChannel;
    private $getStockItemConfiguration;
    private $getSourceItemBySourceCodeAndSku;

    /**
     * We avoid strict types in constructor for create instances dynamically look at
     * \Afterpay\Afterpay\Model\StockItemsValidator\StockItemsValidatorProxy
     * @param \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku // @codingStandardsIgnoreLine
     * @param \Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param \Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface $getStockBySalesChannel
     */
    public function __construct(
        $getSourceItemBySourceCodeAndSku,
        $getStockItemConfiguration,
        $getStockBySalesChannel
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
    }

    /**
     * Check if shipment items have enough quantity in case of no throws exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function execute(\Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface $sourceDeductionRequest): void // @codingStandardsIgnoreLine
    {
        $sourceCode = $sourceDeductionRequest->getSourceCode();
        $salesChannel = $sourceDeductionRequest->getSalesChannel();

        $stockId = $this->getStockBySalesChannel->execute($salesChannel)->getStockId();
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $itemSku = $item->getSku();
            $qty = $item->getQty();
            $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                $itemSku,
                $stockId
            );

            if (!$stockItemConfiguration->isManageStock()) {
                //We don't need to Manage Stock
                continue;
            }

            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);
            if (($sourceItem->getQuantity() - $qty) < 0) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Not all of your products are available in the requested quantity.')
                );
            }
        }
    }
}
