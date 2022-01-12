<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\Checkout;

class CheckoutResultHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    public function handle(array $handlingSubject, array $response): void
    {
        $payment = $this->getPayment($handlingSubject);

        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN,
            $response['token']
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_AUTH_TOKEN_EXPIRES,
            $response['expires']
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_REDIRECT_CHECKOUT_URL,
            $response['redirectCheckoutUrl']
        );
    }

    protected function getPayment(array $handlingSubject): \Magento\Payment\Model\InfoInterface
    {
        $quote = $handlingSubject['quote'];
        return $quote->getPayment();
    }
}
