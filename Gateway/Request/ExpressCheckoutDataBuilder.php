<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request;

class ExpressCheckoutDataBuilder extends \Afterpay\Afterpay\Gateway\Request\Checkout\CheckoutDataBuilder
{
    private \Afterpay\Afterpay\Api\Data\Quote\ExtendedShippingInformationInterface $extendedShippingInformation;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        \Afterpay\Afterpay\Api\Data\Quote\ExtendedShippingInformationInterface $extendedShippingInformation
    ) {
        parent::__construct($url, $productRepository, $searchCriteriaBuilder, $checkCBTCurrencyAvailability);
        $this->extendedShippingInformation = $extendedShippingInformation;
    }

    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];
        $currentCurrencyCode = $quote->getQuoteCurrencyCode();
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $amount = $isCBTCurrencyAvailable ? $quote->getGrandTotal() : $quote->getBaseGrandTotal();
        $currencyCode = $isCBTCurrencyAvailable ? $currentCurrencyCode : $quote->getBaseCurrencyCode();
        $popupOriginUrl = $buildSubject['popup_origin_url'];
        $lastSelectedShippingRate = $this->extendedShippingInformation->getParam(
            $quote,
            \Afterpay\Afterpay\Api\Data\Quote\ExtendedShippingInformationInterface::LAST_SELECTED_SHIPPING_RATE
        );

        $data = [
            'mode' => 'express',
            'storeId' => $quote->getStoreId(),
            'amount' => [
                'amount' => $this->formatPrice($amount),
                'currency' => $currencyCode
            ],
            'merchant' => [
                'popupOriginUrl' => $popupOriginUrl
            ],
            'items' => $this->getItems($quote),
            'merchantReference' => $quote->getReservedOrderId(),
            'shippingOptionIdentifier' => $lastSelectedShippingRate
        ];

        if ($discounts = $this->getDiscounts($quote)) {
            $data['discounts'] = $discounts;
        }

        return $data;
    }
}
