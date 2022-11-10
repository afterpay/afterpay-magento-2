<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

class CaptureProcessor
{
    private \Magento\Payment\Gateway\CommandInterface $authCaptureCommand;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $authCaptureCommand,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory
    ) {
        $this->authCaptureCommand = $authCaptureCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(float $amountToCapture, \Magento\Payment\Model\InfoInterface $payment): void
    {
        if ($amountToCapture > 0) {
            $this->authCaptureCommand->execute([
                'amount' => $amountToCapture,
                'payment' => $this->paymentDataObjectFactory->create($payment)
            ]);
        }
    }
}
