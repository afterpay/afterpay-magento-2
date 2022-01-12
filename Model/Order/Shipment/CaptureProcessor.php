<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\Shipment;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

class CaptureProcessor
{
    private \Magento\Payment\Gateway\CommandInterface $authCaptureCommand;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate $authExpiryDate;
    private \Afterpay\Afterpay\Model\Payment\AmountProcessor\Shipment $shipmentAmountProcessor;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $authCaptureCommand,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate $authExpiryDate,
        \Afterpay\Afterpay\Model\Payment\AmountProcessor\Shipment $shipmentAmountProcessor
    ) {
        $this->authCaptureCommand = $authCaptureCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->authExpiryDate = $authExpiryDate;
        $this->shipmentAmountProcessor = $shipmentAmountProcessor;
    }

    /**
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(\Magento\Sales\Model\Order\Shipment $shipment): void
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $shipment->getOrder()->getPayment();

        $additionalInfo = $payment->getAdditionalInformation();
        $paymentState = $additionalInfo[AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE];

        $amountToCaptureExists = $additionalInfo[AdditionalInformationInterface::AFTERPAY_OPEN_TO_CAPTURE_AMOUNT] > 0;
        $correctStateForAuthCapture =
            $paymentState == PaymentStateInterface::AUTH_APPROVED ||
            $paymentState == PaymentStateInterface::PARTIALLY_CAPTURED;

        if ($amountToCaptureExists && $correctStateForAuthCapture) {
            $this->validateAuthExpiryDate($additionalInfo[AdditionalInformationInterface::AFTERPAY_AUTH_EXPIRY_DATE]);

            $amountToCapture = $this->shipmentAmountProcessor->process($shipment->getItemsCollection(), $payment);

            if ($amountToCapture > 0) {
                $this->authCaptureCommand->execute([
                    'amount' => $amountToCapture,
                    'payment' => $this->paymentDataObjectFactory->create($payment)
                ]);
            }
        }
    }

    /**
     * @throws \Magento\Framework\Exception\PaymentException
     */
    private function validateAuthExpiryDate(string $expires): void
    {
        if ($this->authExpiryDate->isExpired($expires)) {
            throw new \Magento\Framework\Exception\PaymentException(__('Authorization date expired'));
        }
    }
}
