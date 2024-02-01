<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response;

class CaptureVirtualProductsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Magento\Payment\Gateway\CommandInterface $authCaptureCommand;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Afterpay\Afterpay\Model\Payment\AmountProcessor\Order $orderAmountProcessor;
    private \Magento\Payment\Gateway\CommandInterface $voidCommand;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface                       $authCaptureCommand,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Afterpay\Afterpay\Model\Payment\AmountProcessor\Order          $orderAmountProcessor,
        \Magento\Payment\Gateway\CommandInterface                       $voidCommand
    ) {
        $this->authCaptureCommand = $authCaptureCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->orderAmountProcessor = $orderAmountProcessor;
        $this->voidCommand = $voidCommand;
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $itemsToCapture = array_filter(
            $payment->getOrder()->getAllItems(),
            static fn($item) => !$item->getParentItem() && $item->getIsVirtual()
        );

        if (count($itemsToCapture)) {
            $amountToCapture = $this->orderAmountProcessor->process($itemsToCapture, $payment);
            if ($amountToCapture > 0) {
                try {
                    $this->authCaptureCommand->execute([
                        'payment' => $this->paymentDataObjectFactory->create($payment),
                        'amount' => $amountToCapture
                    ]);

                } catch (\Throwable $e) {
                    $commandSubject = ['payment' => $paymentDO];
                    $this->voidCommand->execute($commandSubject);

                    throw $e;
                }
            }
        }
    }
}
