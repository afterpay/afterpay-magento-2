<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Observer;

use Afterpay\Afterpay\Gateway\Config\Config;
use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\PaymentException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Sales\Model\Order;

class AuthCaptureBeforeCompleteOrder implements ObserverInterface
{
    private CommandInterface $authCaptureCommand;

    private PaymentDataObjectFactoryInterface $paymentDataObjectFactory;

    public function __construct(
        CommandInterface                  $authCaptureCommand,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory
    ) {
        $this->authCaptureCommand = $authCaptureCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * Observer that captures the remaining amount when the order status is complete.
     *
     * @throws PaymentException
     * @throws CommandException
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if (!$payment->getMethod() == Config::CODE || $order->getStatus() !== Order::STATE_COMPLETE) {
            return;
        }

        $openToCapture = $payment->getAdditionalInformation(
            AdditionalInformationInterface::AFTERPAY_OPEN_TO_CAPTURE_AMOUNT
        );

        if ($openToCapture > 0) {
            $this->authCaptureCommand->execute([
                'amount'  => $openToCapture,
                'payment' => $this->paymentDataObjectFactory->create($payment)
            ]);
        }
    }
}
