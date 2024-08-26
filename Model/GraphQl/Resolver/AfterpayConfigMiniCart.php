<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class AfterpayConfigMiniCart extends AfterpayConfigCart implements ResolverInterface
{
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
        $result = parent::resolve($field, $context, $info, $value, $args);

        $websiteId = $result['website_id'];
        $result['is_enabled_cta_minicart_headless'] = $this->config->getIsEnableMiniCartHeadless($websiteId);
        $result['is_enabled_ec_minicart_headless'] = $this->config->getIsEnableMiniCartHeadless($websiteId);
        $result['placement_wrapper'] = $this->config->getMiniCartPlacementContainerSelector($websiteId);
        $result['placement_after_selector'] = $this->config->getMiniCartPlacementAfterSelector($websiteId);
        $result['price_selector'] = $this->config->getMiniCartPlacementPriceSelector($websiteId);

        return $result;
    }
}
