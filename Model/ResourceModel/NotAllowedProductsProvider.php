<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\ResourceModel;

class NotAllowedProductsProvider
{
    private \Afterpay\Afterpay\Model\Config $config;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __construct(
        \Afterpay\Afterpay\Model\Config           $config,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
    }

    public function provideIds(?int $storeId = null): array
    {
        $excludedCategoriesIds = $this->config->getExcludeCategories($storeId);
        if (empty($excludedCategoriesIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['cat' => $this->resourceConnection->getTableName('catalog_category_product')],
            'cat.product_id'
        )->where($connection->prepareSqlCondition('cat.category_id', ['in' => $excludedCategoriesIds]));

        return $connection->fetchCol($select);
    }

    public function isProductAllowed(\Magento\Catalog\Api\Data\ProductInterface $product, ?int $storeId = null): bool
    {
        $notAllowedProductIds = array_map('intval', $this->provideIds($storeId));
        if (in_array((int)$product->getId(), $notAllowedProductIds, true)) {
            return false;
        }

        $excludedCategoriesIds = $this->config->getExcludeCategories($storeId);
        if (empty($excludedCategoriesIds)) {
            return true;
        }

        foreach ($product->getCategoryIds() as $categoryId) {
            if (in_array((int)$categoryId, $excludedCategoriesIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function getRestrictedSkusFromQuote(\Magento\Quote\Model\Quote $quote): array
    {
        $notAllowedProductIds = array_flip($this->provideIds((int)$quote->getStoreId()));
        if (!$notAllowedProductIds) {
            return [];
        }

        $restrictedSkus = [];
        foreach ($quote->getAllItems() as $item) {
            if (isset($notAllowedProductIds[(int)$item->getProductId()])) {
                $restrictedSkus[] = (string)$item->getSku();
            }
        }

        return array_values(array_unique($restrictedSkus));
    }

    public function hasRestrictedProductsInQuote(\Magento\Quote\Model\Quote $quote): bool
    {
        return $this->getRestrictedSkusFromQuote($quote) !== [];
    }
}
