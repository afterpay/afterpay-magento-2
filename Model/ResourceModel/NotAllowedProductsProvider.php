<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\ResourceModel;

class NotAllowedProductsProvider
{
    private $config;
    private $connection;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->config = $config;
        $this->connection = $resourceConnection->getConnection();
    }

    public function provideIds(?int $storeId = null): array
    {
        $excludedCategoriesIds = $this->config->getExcludeCategories($storeId);
        if (empty($excludedCategoriesIds)) {
            return [];
        }

        $select = $this->connection->select()->from(
            ['cat' => $this->connection->getTableName('catalog_category_product')],
            'cat.product_id'
        )->where($this->connection->prepareSqlCondition('cat.category_id', ['in' => $excludedCategoriesIds]));

        return $this->connection->fetchCol($select);
    }
}
