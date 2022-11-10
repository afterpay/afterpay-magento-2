<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;

class CancelOrderProcessor
{
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Magento\Payment\Gateway\CommandInterface $refundCommand;
    private \Magento\Payment\Gateway\CommandInterface $voidCommand;

    public function __construct(
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Payment\Gateway\CommandInterface $refundCommand,
        \Magento\Payment\Gateway\CommandInterface $voidCommand
    ) {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->refundCommand = $refundCommand;
        $this->voidCommand = $voidCommand;
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(\Magento\Sales\Model\Order\Payment $payment): void
    {
        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($payment)];
        $paymentState = $payment->getAdditionalInformation(AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE);

        if ($paymentState == \Afterpay\Afterpay\Model\PaymentStateInterface::AUTH_APPROVED) {
            $this->voidCommand->execute($commandSubject);
        } else {
            $isCBTCurrency = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);
            $this->refundCommand->execute(array_merge($commandSubject, [
                'amount' => $isCBTCurrency ? $payment->getAmountOrdered() : $payment->getBaseAmountOrdered()
            ]));
        }
    }
}
