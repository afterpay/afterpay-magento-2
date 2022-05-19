<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

class SourceValidatorService implements \Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface
{
    private \Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface $getStockBySalesChannel;
    private \Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface $getStockItemConfiguration;
    private \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku;

    public function __construct(
        \Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        \Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface $getStockItemConfiguration,
        \Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface $getStockBySalesChannel
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface $sourceDeductionRequest): void
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
