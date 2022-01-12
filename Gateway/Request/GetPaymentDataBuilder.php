<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;

class GetPaymentDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $afterpayOrderId = $payment->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ORDER_ID
        );
        return [
            'storeId' => $payment->getOrder()->getStoreId(),
            'orderId' => $afterpayOrderId,
        ];
    }
}
