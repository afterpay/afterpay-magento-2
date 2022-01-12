<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\PaymentAction;

class CaptureDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN);
        $data = [
            'storeId' => $paymentDO->getOrder()->getStoreId(),
            'token' => $token
        ];

        if ($payment->getAdditionalInformation('afterpay_express')) {
            $data['amount'] = [
                'amount' => \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($buildSubject),
                'currency' => $payment->getOrder()->getOrderCurrencyCode()
            ];
        }

        return $data;
    }
}
