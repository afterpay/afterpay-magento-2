<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Command;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;

class RefundAndVoidCommand implements \Magento\Payment\Gateway\CommandInterface
{
    private \Magento\Payment\Gateway\CommandInterface $refundCommand;
    private \Magento\Payment\Gateway\CommandInterface $voidCommand;
    private \Afterpay\Afterpay\Model\Payment\AmountProcessor\CreditMemo $creditMemoAmountProcessor;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $refundCommand,
        \Magento\Payment\Gateway\CommandInterface $voidCommand,
        \Afterpay\Afterpay\Model\Payment\AmountProcessor\CreditMemo $creditMemoAmountProcessor
    ) {
        $this->refundCommand = $refundCommand;
        $this->voidCommand = $voidCommand;
        $this->creditMemoAmountProcessor = $creditMemoAmountProcessor;
    }

    public function execute(array $commandSubject): ?\Magento\Payment\Gateway\Command\ResultInterface
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($commandSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        [$amountToRefund, $amountToVoid] = $this->creditMemoAmountProcessor->process($payment);

        $refundCommandSubject = array_merge(
            $commandSubject,
            ['amount' => $amountToRefund]
        );
        $voidCommandSubject = array_merge(
            $commandSubject,
            ['amount' => $amountToVoid]
        );

        if (\Magento\Payment\Gateway\Helper\SubjectReader::readAmount($refundCommandSubject) > 0) {
            $this->refundCommand->execute($refundCommandSubject);
        }
        if (\Magento\Payment\Gateway\Helper\SubjectReader::readAmount($voidCommandSubject) > 0) {
            $this->voidCommand->execute($voidCommandSubject);
        }

        return null;
    }
}
