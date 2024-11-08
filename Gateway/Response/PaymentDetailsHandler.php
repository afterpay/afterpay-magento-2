<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response;

class PaymentDetailsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate $authExpiryDate;

    private \Afterpay\Afterpay\Model\Order\CreditMemo\PaymentUpdater\Proxy $paymentUpdater;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate   $authExpiryDate,
        \Afterpay\Afterpay\Model\Order\CreditMemo\PaymentUpdater\Proxy $paymentUpdater
    ) {
        $this->authExpiryDate = $authExpiryDate;
        $this->paymentUpdater = $paymentUpdater;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($response['id'])) {
            if (isset($response['errorCode'], $response['errorId'])) {
                throw new \Magento\Payment\Gateway\Command\CommandException(
                    __(
                        'Afterpay response error: Code: %1, Id: %2',
                        $response['errorCode'],
                        $response['errorId']
                    )
                );
            }

            $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
            $this->paymentUpdater->updatePayment($paymentDO->getPayment());

            return;
        }

        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setTransactionId($response['id']);
        $payment->setIsTransactionClosed($response['openToCaptureAmount']['amount'] == 0);

        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ORDER_ID,
            $response['id']
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN,
            $response['token']
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_OPEN_TO_CAPTURE_AMOUNT,
            $response['openToCaptureAmount']['amount']
        );
        $payment->setAdditionalInformation(
            \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE,
            $response['paymentState']
        );
        if (isset($response['events'][0]['expires']) && $expires = $response['events'][0]['expires']) {
            $payment->setAdditionalInformation(
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_AUTH_EXPIRY_DATE,
                $this->authExpiryDate->format($expires)
            );
        }
    }
}
