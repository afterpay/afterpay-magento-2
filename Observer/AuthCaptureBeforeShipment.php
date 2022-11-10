<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Observer;

class AuthCaptureBeforeShipment implements \Magento\Framework\Event\ObserverInterface
{
    private $shipmentCaptureProcessor;

    private $ckeckPaymentMethod;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\Shipment\CaptureProcessor $shipmentCaptureProcessor,
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $ckeckPaymentMethod
    ) {
        $this->shipmentCaptureProcessor = $shipmentCaptureProcessor;
        $this->ckeckPaymentMethod = $ckeckPaymentMethod;
    }

    /**
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getData('shipment');

        /** @var \Magento\Sales\Model\Order\Payment $paymentInfo */
        $paymentInfo = $shipment->getOrder()->getPayment();

        if (!$this->ckeckPaymentMethod->isAfterPayMethod($paymentInfo)) {
            return;
        }

        $this->shipmentCaptureProcessor->execute($shipment);
    }
}
