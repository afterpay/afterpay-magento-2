<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\Checkout;

class CheckoutDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    private $url;
    private $productRepository;
    private $searchCriteriaBuilder;
    protected $checkCBTCurrencyAvailability;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability
    ) {
        $this->url = $url;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
    }

    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];
        /** @var \Afterpay\Afterpay\Api\Data\RedirectPathInterface $redirectPath */
        $redirectPath = $buildSubject['redirect_path'];

        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        $billingTaxAmount = $isCBTCurrencyAvailable
            ? $billingAddress->getTaxAmount()
            : $billingAddress->getBaseTaxAmount();
        $shippingTaxAmount = $isCBTCurrencyAvailable
            ? $shippingAddress->getTaxAmount()
            : $shippingAddress->getBaseTaxAmount();

        $data = [
            'storeId' => $quote->getStoreId(),
            'amount' => [
                'amount' => $this->formatPrice(
                    $isCBTCurrencyAvailable ? $quote->getGrandTotal() : $quote->getBaseGrandTotal()
                ),
                'currency' => $isCBTCurrencyAvailable ? $quote->getQuoteCurrencyCode() : $quote->getBaseCurrencyCode()
            ],
            'consumer' => [
                'givenNames' => $quote->getCustomerFirstname() ?: $billingAddress->getFirstname(),
                'surname' => $quote->getCustomerLastname() ?: $billingAddress->getLastname(),
                'email' => $quote->getCustomerEmail() ?: $billingAddress->getEmail(),
                'phoneNumber' => $billingAddress->getTelephone()
            ],
            'billing' => [
                'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                'line1' => $billingAddress->getStreetLine(1),
                'line2' => $this->implodeStreetLines($billingAddress, [2,3]),
                'area1' => $billingAddress->getCity(),
                'region' => $billingAddress->getRegion(),
                'postcode' => $billingAddress->getPostcode(),
                'countryCode' => $billingAddress->getCountryId(),
                'phoneNumber' => $billingAddress->getTelephone()
            ],
            'items' => $this->getItems($quote),
            'merchant' => [
                'redirectConfirmUrl' => $this->url->getUrl($redirectPath->getConfirmPath()),
                'redirectCancelUrl' => $this->url->getUrl($redirectPath->getCancelPath())
            ],
            'merchantReference' => $quote->getReservedOrderId(),
            'taxAmount' => [
                'amount' => $this->formatPrice(
                    $billingTaxAmount ?: $shippingTaxAmount
                ),
                'currency' => $isCBTCurrencyAvailable ? $quote->getQuoteCurrencyCode() : $quote->getBaseCurrencyCode()
            ],
            'purchaseCountry' => $billingAddress->getCountryId()
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
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);

        foreach ($quoteItems as $item) {
            $productId = $item->getProduct()->getId();
            $amount = $isCBTCurrencyAvailable ? $item->getPriceInclTax() : $item->getBasePriceInclTax();
            $currencyCode = $isCBTCurrencyAvailable ? $quote->getQuoteCurrencyCode() : $quote->getBaseCurrencyCode();
            $qty = $item->getQty();
            $isIntQty = floor($qty) == $qty;
            if ($isIntQty) {
                $qty = (int)$item->getQty();
            } else {
                $amount *= $item->getQty();
                $qty = 1;
            }

            $formattedItem = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => $qty,
                'pageUrl' => $item->getProduct()->getProductUrl(),
                'categories' => [array_values($this->getQuoteItemCategoriesNames($item))],
                'price' => [
                    'amount' => $this->formatPrice($amount),
                    'currency' => $currencyCode
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
        return array_map(static function ($cat) {return $cat->getData('name');}, $itemCategories);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @return array
     */
    protected function getItemsImages(array $items): array
    {
        $itemsImages = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', array_map(static function ($item) {return $item->getProduct()->getId();}, $items), 'in') // @codingStandardsIgnoreLine
            ->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();

        foreach ($products as $product) {
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
            'countryCode' => $shippingAddress->getCountryId(),
            'phoneNumber' => $shippingAddress->getTelephone()
        ];
    }

    protected function getShippingAmount(\Magento\Quote\Model\Quote $quote): ?array
    {
        if ($quote->isVirtual()) {
            return null;
        }
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $amount = $isCBTCurrencyAvailable
            ? $quote->getShippingAddress()->getShippingAmount()
            : $quote->getShippingAddress()->getBaseShippingAmount();
        $currencyCode = $isCBTCurrencyAvailable ? $quote->getQuoteCurrencyCode() : $quote->getBaseCurrencyCode();

        return [
            'amount' => $this->formatPrice($amount),
            'currency' => $currencyCode
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
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $amount = $isCBTCurrencyAvailable
            ? $quote->getDiscountAmount()
            : $quote->getBaseDiscountAmount();
        $currencyCode = $isCBTCurrencyAvailable ? $quote->getQuoteCurrencyCode() : $quote->getBaseCurrencyCode();

        return [
            'displayName' => __('Discount'),
            'amount' => [
                'amount' => $this->formatPrice($amount),
                'currency' => $currencyCode
            ]
        ];
    }
}
