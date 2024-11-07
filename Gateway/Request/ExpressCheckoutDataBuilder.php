<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request;

class ExpressCheckoutDataBuilder extends \Afterpay\Afterpay\Gateway\Request\Checkout\CheckoutDataBuilder
{
    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];
        $currentCurrencyCode = $quote->getQuoteCurrencyCode();
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $amount = $isCBTCurrencyAvailable ? $quote->getGrandTotal() : $quote->getBaseGrandTotal();
        $currencyCode = $isCBTCurrencyAvailable ? $currentCurrencyCode : $quote->getBaseCurrencyCode();
        $popupOriginUrl = $buildSubject['popup_origin_url'];

        $lastSelectedShippingRate = null;
        if ($quote->getShippingAddress() && $quote->getShippingAddress()->getShippingMethod()) {
            $lastSelectedShippingRate = $quote->getShippingAddress()->getShippingMethod();
        }

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
