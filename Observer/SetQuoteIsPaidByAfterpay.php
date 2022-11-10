<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Observer;

class SetQuoteIsPaidByAfterpay implements \Magento\Framework\Event\ObserverInterface
{
    private \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;
    private \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage,
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod
    ) {
        $this->quotePaidStorage = $quotePaidStorage;
        $this->checkPaymentMethod = $checkPaymentMethod;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getData('payment');

        if ($this->checkPaymentMethod->isAfterPayMethod($payment)) {
            $this->quotePaidStorage->setAfterpayPaymentForQuote((int)$payment->getOrder()->getQuoteId(), $payment);
        }
    }
}
