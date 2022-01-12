<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Observer;

class SetQuoteIsPaidByAfterpay implements \Magento\Framework\Event\ObserverInterface
{
    private \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage
    ) {
        $this->quotePaidStorage = $quotePaidStorage;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getData('payment');

        if ($payment->getMethod() == \Afterpay\Afterpay\Gateway\Config\Config::CODE) {
            $this->quotePaidStorage->setAfterpayPaymentForQuote((int)$payment->getOrder()->getQuoteId(), $payment);
        }
    }
}
