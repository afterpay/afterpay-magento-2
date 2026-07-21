<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Resolver;

use Afterpay\Afterpay\Model\Config;
use Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session;
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
    private Session $checkoutSession;
    private NotAllowedProductsProvider $notAllowedProductsProvider;

    public function __construct(
        Config $config,
        StoreManagerInterface           $storeManager,
        ProductRepositoryInterface      $productRepository,
        StockRegistryInterface          $stockRegistry,
        Session                         $checkoutSession,
        NotAllowedProductsProvider      $notAllowedProductsProvider
    ) {
        parent::__construct($config, $storeManager);
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->notAllowedProductsProvider = $notAllowedProductsProvider;
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
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
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
        $result['not_allowed_product_ids'] = array_map(
            'strval',
            $this->notAllowedProductsProvider->provideIds((int)$storeId)
        );
        $result['is_product_allowed'] = $this->notAllowedProductsProvider->isProductAllowed($product, (int)$storeId);

        $quote = $this->checkoutSession->getQuote();
        $result['has_restricted_products_in_cart'] = $quote && $quote->getId()
            ? $this->notAllowedProductsProvider->hasRestrictedProductsInQuote($quote)
            : false;

        // Add stock status
        $stockItem = $this->stockRegistry->getStockItemBySku($productSku);
        $result['is_in_stock'] = (bool)$stockItem->getIsInStock();

        $result['placement_id'] = $this->config->getPlacementIdPdp($websiteId); // Dynamic Message ID

        return $result;
    }
}
