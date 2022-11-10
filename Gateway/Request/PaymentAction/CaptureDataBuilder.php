<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\PaymentAction;

class CaptureDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $payment->getOrder();

        $isCBTCurrency = (bool) $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY
        );
        $cbtCurrency = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_CBT_CURRENCY
        );
        $currencyCode = ($isCBTCurrency && $cbtCurrency) ? $cbtCurrency : $order->getBaseCurrencyCode();

        $token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN);
        $data = [
            'storeId' => $order->getStoreId(),
            'token' => $token
        ];

        if ($payment->getAdditionalInformation('afterpay_express')) {
            $data['amount'] = [
                'amount' => \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($buildSubject),
                'currency' => $currencyCode
            ];
        }

        return $data;
    }
}
