<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\PaymentAction;

use Magento\Payment\Gateway\Helper\SubjectReader;

class ReversalDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $payment = SubjectReader::readPayment($buildSubject);
        $afterpayToken = $payment->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN
        );

        return [
            'storeId' => $payment->getOrder()->getStoreId(),
            'afterpayToken' => $afterpayToken
        ];
    }
}
