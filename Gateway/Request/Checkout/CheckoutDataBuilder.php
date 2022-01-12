<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\Checkout;

class CheckoutDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    private \Magento\Framework\UrlInterface $url;
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->url = $url;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];
        /** @var \Afterpay\Afterpay\Api\Data\RedirectPathInterface $redirectPath */
        $redirectPath = $buildSubject['redirect_path'];

        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        $data = [
            'storeId' => $quote->getStoreId(),
            'amount' => [
                'amount' => $this->formatPrice($quote->getBaseGrandTotal()),
                'currency' => $quote->getBaseCurrencyCode()
            ],
            'consumer' => [
                'givenNames' => $quote->getCustomerFirstname() ?: $billingAddress->getFirstname(),
                'surname' => $quote->getCustomerLastname() ?: $billingAddress->getLastname(),
                'email' => $quote->getCustomerEmail() ?: $billingAddress->getEmail()
            ],
            'billing' => [
                'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                'line1' => $billingAddress->getStreetLine(1),
                'line2' => $this->implodeStreetLines($billingAddress, [2,3]),
                'area1' => $billingAddress->getCity(),
                'region' => $billingAddress->getRegion(),
                'postcode' => $billingAddress->getPostcode(),
                'countryCode' => $billingAddress->getCountryId()
            ],
            'items' => $this->getItems($quote),
            'merchant' => [
                'redirectConfirmUrl' => $this->url->getUrl($redirectPath->getConfirmPath()),
                'redirectCancelUrl' => $this->url->getUrl($redirectPath->getCancelPath())
            ],
            'merchantReference' => $quote->getReservedOrderId(),
            'taxAmount' => [
                'amount' => $this->formatPrice(
                    $billingAddress->getBaseTaxAmount() ?: $shippingAddress->getBaseTaxAmount()
                ),
                'currency' => $quote->getBaseCurrencyCode()
            ]
        ];

        if ($shippingAddress = $this->getShippingAddress($quote)) {
            $data['shipping'] = $shippingAddress;
        }
        if ($shippingAmount = $this->getShippingAmount($quote)) {
            $data['shippingAmount'] = $shippingAmount;
        }
        if ($discounts = $this->getDiscounts($quote)) {
            $data['discounts'] = $discounts;
        }

        return $data;
    }

    protected function getItems(\Magento\Quote\Model\Quote $quote): array
    {
        $formattedItems = [];
        $quoteItems = $quote->getAllVisibleItems();
        $itemsImages = $this->getItemsImages($quoteItems);

        foreach ($quoteItems as $item) {
            $productId = $item->getProduct()->getId();

            $formattedItem = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQty(),
                'pageUrl' => $item->getProduct()->getProductUrl(),
                'categories' => [array_values($this->getQuoteItemCategoriesNames($item))],
                'price' => [
                    'amount' => $this->formatPrice($item->getBasePriceInclTax()),
                    'currency' => $quote->getBaseCurrencyCode()
                ]
            ];

            if (isset($itemsImages[$productId]) && $image = $itemsImages[$productId]) {
                if ($imageUrl = $image->getUrl()) {
                    $formattedItem['imageUrl'] = $imageUrl;
                }
            }

            $formattedItems[] = $formattedItem;
        }
        return $formattedItems;
    }

    protected function getQuoteItemCategoriesNames(\Magento\Quote\Model\Quote\Item $item): array
    {
        /** @var \Magento\Catalog\Model\ResourceModel\AbstractCollection $categoryCollection */
        $categoryCollection = $item->getProduct()->getCategoryCollection();
        $itemCategories = $categoryCollection->addAttributeToSelect('name')->getItems();
        return array_map(static fn ($cat) => $cat->getData('name'), $itemCategories);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @return array
     */
    protected function getItemsImages(array $items): array
    {
        $itemsImages = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', array_map(static fn ($item) => $item->getProduct()->getId(), $items), 'in')
            ->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();

        foreach ($items as $item) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $products[$item->getProduct()->getId()];
            $medialGalleryImages = $product->getMediaGalleryImages();
            $itemsImages[$product->getId()] = $medialGalleryImages->getFirstItem();
        }
        return $itemsImages;
    }

    protected function getShippingAddress(\Magento\Quote\Model\Quote $quote): ?array
    {
        if ($quote->isVirtual()) {
            return null;
        }
        $shippingAddress = $quote->getShippingAddress();
        return [
            'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
            'line1' => $shippingAddress->getStreetLine(1),
            'line2' => $this->implodeStreetLines($shippingAddress, [2,3]),
            'area1' => $shippingAddress->getCity(),
            'region' => $shippingAddress->getRegion(),
            'postcode' => $shippingAddress->getPostcode(),
            'countryCode' => $shippingAddress->getCountryId()
        ];
    }

    protected function getShippingAmount(\Magento\Quote\Model\Quote $quote): ?array
    {
        if ($quote->isVirtual()) {
            return null;
        }
        return [
            'amount' => $this->formatPrice($quote->getShippingAddress()->getBaseShippingAmount()),
            'currency' => $quote->getBaseCurrencyCode()
        ];
    }

    protected function implodeStreetLines(\Magento\Quote\Model\Quote\Address $address, array $lines): string
    {
        $streetLines = [];
        foreach ($lines as $line) {
            if ($streetLine = $address->getStreetLine($line)) {
                $streetLines[] = $streetLine;
            }
        }
        return implode(', ', $streetLines);
    }

    protected function getDiscounts(\Magento\Quote\Model\Quote $quote): ?array
    {
        if (!$quote->getBaseDiscountAmount()) {
            return null;
        }
        return [
            'displayName' => __('Discount'),
            'amount' => [
                'amount' => $this->formatPrice($quote->getBaseDiscountAmount()),
                'currency' => $quote->getBaseCurrencyCode()
            ]
        ];
    }
}
