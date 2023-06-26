<?php

namespace Afterpay\Afterpay\Plugin\QuoteGraphQl\Cart;

use Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface;
use Afterpay\Afterpay\Model\Payment\PaymentErrorProcessor;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderModel;

class PlaceOrderPlugin
{
    private PaymentErrorProcessor $paymentErrorProcessor;
    private CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;

    public function __construct(
        PaymentErrorProcessor                 $paymentErrorProcessor,
        CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability
    ) {
        $this->paymentErrorProcessor = $paymentErrorProcessor;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
    }

    public function aroundExecute(
        PlaceOrderModel $subject,
        callable        $proceed,
                        $cart,
                        $maskedCartId,
                        $userId
    ) {
        try {
            $payment = $cart->getPayment();

            if ($payment->getMethod() === 'afterpay') {
                $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($cart);
                $payment->setAdditionalInformation(
                    \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY,
                    $isCBTCurrencyAvailable
                );
                $payment->setAdditionalInformation(
                    \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_CBT_CURRENCY,
                    $cart->getQuoteCurrencyCode()
                );
            }

            return $proceed($cart, $maskedCartId, $userId);
        } catch (\Throwable $e) {
            if ($payment->getMethod() === 'afterpay') {
                return (int)$this->paymentErrorProcessor->execute($cart, $e, $payment);
            }

            throw $e;
        }
    }
}
