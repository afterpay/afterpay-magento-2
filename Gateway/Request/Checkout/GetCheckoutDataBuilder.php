<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\Checkout;

use Magento\Payment\Gateway\Helper\SubjectReader;

class GetCheckoutDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
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
