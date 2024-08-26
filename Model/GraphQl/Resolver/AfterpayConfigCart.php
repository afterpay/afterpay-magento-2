<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Resolver;

use Afterpay\Afterpay\Model\Config;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Store\Model\StoreManagerInterface;

class AfterpayConfigCart extends AfterpayConfig implements ResolverInterface
{
    private CartRepositoryInterface $cartRepository;

    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;

    public function __construct(
        Config                          $config,
        StoreManagerInterface           $storeManager,
        CartRepositoryInterface         $cartRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        parent::__construct($config, $storeManager);
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
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
        $cartId = $args['input']['cart_id'];
        if (!is_numeric($cartId) && !empty($cartId)) {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        }
        $cart = $this->cartRepository->get($cartId);
        $store = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store);
        $websiteId = (int)$store->getWebsiteId();

        $result = parent::resolve($field, $context, $info, $value, $args);

        $result['website_id'] = $websiteId;
        $result['is_enabled_cta_cart_page_headless'] = $this->config->getIsEnableCartPageHeadless($websiteId);
        $result['is_enabled_ec_cart_page_headless'] = $this->config->getIsEnableCartPageHeadless($websiteId);
        $result['placement_after_selector'] = $this->config->getCartPagePlacementAfterSelector($websiteId);
        $result['price_selector'] = $this->config->getCartPagePlacementPriceSelector($websiteId);
        $result['show_lover_limit'] = $this->config->getMinOrderTotal($websiteId) >= 1;
        $result['is_cbt_enabled'] = count($this->config->getSpecificCountries($websiteId)) > 1;
        $result['is_product_allowed'] = true;
        $result['is_virtual'] = $cart->isVirtual();
        $excludedCategoriesIds = $this->config->getExcludeCategories((int)$storeId);
        if (!empty($excludedCategoriesIds)) {
            foreach ($cart->getAllVisibleItems() as $item) {
                foreach ($item->getProduct()->getCategoryIds() as $categoryId) {
                    if (in_array($categoryId, $excludedCategoriesIds)) {
                        $result['is_product_allowed'] = false;
                        break;
                    }
                }
            }
        }

        return $result;
    }
}
