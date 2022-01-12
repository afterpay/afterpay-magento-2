<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\PaymentAction;

use Magento\Payment\Gateway\Helper\SubjectReader;

class AuthCaptureDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $afterpayOrderId = $payment->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ORDER_ID
        );

        return [
            'storeId' => $paymentDO->getOrder()->getStoreId(),
            'orderId' => $afterpayOrderId,
            'amount' => [
                'amount' => $this->formatPrice(SubjectReader::readAmount($buildSubject)),
                'currency' => $payment->getOrder()->getOrderCurrencyCode()
            ]
        ];
    }
}
