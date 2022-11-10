<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Observer;

class SetQuoteIsPaidByAfterpay implements \Magento\Framework\Event\ObserverInterface
{
    private $quotePaidStorage;

    private $ckeckPaymentMethod;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage,
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $ckeckPaymentMethod
    ) {
        $this->quotePaidStorage = $quotePaidStorage;
        $this->ckeckPaymentMethod = $ckeckPaymentMethod;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getData('payment');

        if ($this->ckeckPaymentMethod->isAfterPayMethod($payment)) {
            $this->quotePaidStorage->setAfterpayPaymentForQuote((int)$payment->getOrder()->getQuoteId(), $payment);
        }
    }
}
