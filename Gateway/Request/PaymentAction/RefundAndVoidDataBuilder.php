<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request\PaymentAction;

use Magento\Payment\Gateway\Helper\SubjectReader;

class RefundAndVoidDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $afterpayOrderId = $paymentDO->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ORDER_ID
        );

        $isCBTCurrency = (bool) $paymentDO->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY
        );
        $CBTCurrency = $paymentDO->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_CBT_CURRENCY
        );
        $currencyCode = ($isCBTCurrency && $CBTCurrency) ? $CBTCurrency : $paymentDO->getOrder()->getCurrencyCode();

        $data = [
            'storeId' => $paymentDO->getOrder()->getStoreId(),
            'orderId' => $afterpayOrderId
        ];
        try {
            return array_merge($data, [
                'amount' => [
                    'amount' => $this->formatPrice(SubjectReader::readAmount($buildSubject)),
                    'currency' => $currencyCode
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return $data;
        }
    }
}
