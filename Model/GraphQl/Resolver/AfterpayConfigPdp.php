<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Resolver;

use Afterpay\Afterpay\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;

class AfterpayConfigPdp extends AfterpayConfig implements ResolverInterface
{
    private ProductRepositoryInterface $productRepository;
    private StockRegistryInterface $stockRegistry;

    public function __construct(
        Config $config,
        StoreManagerInterface           $storeManager,
        ProductRepositoryInterface      $productRepository,
        StockRegistryInterface          $stockRegistry
    ) {
        parent::__construct($config, $storeManager);
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field            $field
     * @param ContextInterface $context
     * @param ResolveInfo      $info
     * @param array|null       $value
     * @param array|null       $args
     *
     * @return mixed|Value
     * @throws \Exception
     */
    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        if (!$args || !$args['input']) {
            throw new \InvalidArgumentException('Required params cart_id and redirect_path are missing');
        }

        $storeId = $args['input']['store_id'];
        $productSku = $args['input']['product_sku'];
        $product = $this->productRepository->get($productSku);
        $store = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store);
        $websiteId = (int)$store->getWebsiteId();

        $result = parent::resolve($field, $context, $info, $value, $args);

        $result['product_type'] = $product->getTypeId();
        $result['is_enabled_cta_pdp_headless'] = $this->config->getIsEnableProductPageHeadless($websiteId);
        $result['is_enabled_ec_pdp_headless'] = $this->config->getIsEnableProductPageHeadless($websiteId);
        $result['placement_after_selector'] = $this->config->getPdpPlacementAfterSelector($websiteId);
        $result['price_selector'] = $this->config->getPdpPlacementPriceSelector($websiteId);
        $result['placement_after_selector_bundle'] = $this->config->getPdpPlacementAfterSelectorBundle($websiteId);
        $result['price_selector_bundle'] = $this->config->getPdpPlacementPriceSelectorBundle($websiteId);
        $result['show_lover_limit'] = $this->config->getMinOrderTotal($websiteId) >= 1;
        $result['is_cbt_enabled'] = count($this->config->getSpecificCountries($websiteId)) > 1;
        $result['is_product_allowed'] = true;
        $excludedCategoriesIds = $this->config->getExcludeCategories((int)$storeId);
        if (!empty($excludedCategoriesIds)) {
            foreach ($product->getCategoryIds() as $categoryId) {
                if (in_array($categoryId, $excludedCategoriesIds)) {
                    $result['is_product_allowed'] = false;
                    break;
                }
            }
        }

        // Add stock status
        $stockItem = $this->stockRegistry->getStockItemBySku($productSku);
        $result['is_in_stock'] = (bool)$stockItem->getIsInStock();

        return $result;
    }
}
