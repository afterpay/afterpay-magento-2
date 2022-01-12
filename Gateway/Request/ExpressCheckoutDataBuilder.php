<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request;

class ExpressCheckoutDataBuilder extends \Afterpay\Afterpay\Gateway\Request\Checkout\CheckoutDataBuilder
{
    public function build(array $buildSubject): array
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $buildSubject['quote'];

        $popupOriginUrl = $buildSubject['popup_origin_url'];

        $data = [
            'mode' => 'express',
            'storeId' => $quote->getStoreId(),
            'amount' => [
                'amount' => $this->formatPrice($quote->getBaseGrandTotal()),
                'currency' => $quote->getBaseCurrencyCode()
            ],
            'merchant' => [
                'popupOriginUrl' => $popupOriginUrl
            ],
            'items' => $this->getItems($quote),
            'merchantReference' => $quote->getReservedOrderId()
        ];

        if ($discounts = $this->getDiscounts($quote)) {
            $data['discounts'] = $discounts;
        }

        return $data;
    }
}
