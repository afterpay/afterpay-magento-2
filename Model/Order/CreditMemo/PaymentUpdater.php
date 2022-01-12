<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

class PaymentUpdater
{
    private \Magento\Payment\Gateway\CommandInterface $getPaymentDataCommand;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $getPaymentDataCommand,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        $this->getPaymentDataCommand = $getPaymentDataCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    public function updatePayment(
        \Magento\Payment\Model\InfoInterface $payment
    ): \Magento\Sales\Api\Data\OrderPaymentInterface {
        $this->getPaymentDataCommand->execute([
            'payment' => $this->paymentDataObjectFactory->create($payment)
        ]);
        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $this->orderPaymentRepository->save($payment);
        return $payment;
    }
}
