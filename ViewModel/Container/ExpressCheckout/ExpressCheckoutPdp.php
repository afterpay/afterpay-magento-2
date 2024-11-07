<?php declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\ExpressCheckout;

class ExpressCheckoutPdp extends ExpressCheckout
{
    private $catalogHelper;
    private $productCollectionFactory;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        parent::__construct($serializer, $config, $notAllowedProductsProvider, $storeManager, $localeResolver);
        $this->catalogHelper = $catalogHelper;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function updateJsLayout(
        string $jsLayoutJson,
        bool $remove = false,
        string $containerNodeName = 'afterpay.express.checkout',
        array $config = []
    ): string {
        if (!$remove && $this->isContainerEnable()) {
            $product = $this->catalogHelper->getProduct();
            if ($product) {
                $config['isVirtual'] = $this->isProductVirtual($product);
            }
            $config['buttonImageUrl'] = 'https://static.afterpay.com/'.str_replace("_","-",$this->localeResolver->getLocale()).'/integration/button/checkout-with-afterpay/white-on-black.svg';

        }
        return parent::updateJsLayout($jsLayoutJson, $remove, $containerNodeName, $config);
    }

    protected function isProductVirtual(\Magento\Catalog\Model\Product $product): bool
    {
        $productsArray = [$product];
        if ($product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $bundleType = $product->getTypeInstance();
            $childrenIds = $bundleType->getChildrenIds($product->getId());
            $productCollection = $this->productCollectionFactory->create();
            $productsArray = $productCollection->addFieldToSelect('type_id')
                ->addFieldToFilter('entity_id', ['in' => $childrenIds])->getItems();
        } elseif ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $productsArray = $product->getTypeInstance()->getUsedProducts($product);
        }
        return array_reduce(
            $productsArray,
            function (bool $isVirtual, \Magento\Catalog\Api\Data\ProductInterface $item): bool {
                return $isVirtual && $item->getIsVirtual();
            },
            true
        );
    }
}
