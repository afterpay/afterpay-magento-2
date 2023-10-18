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
}
