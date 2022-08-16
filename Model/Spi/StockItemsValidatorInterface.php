<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Spi;

interface StockItemsValidatorInterface
{
    /**
     * Check if shipment items have enough quantity in case of no throws exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function validate(\Magento\Sales\Model\Order\Shipment $shipment): void;
}
