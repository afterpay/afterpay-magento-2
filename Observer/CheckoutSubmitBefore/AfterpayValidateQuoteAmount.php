<?php

namespace Afterpay\Afterpay\Observer\CheckoutSubmitBefore;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class AfterpayValidateQuoteAmount implements ObserverInterface
{
    // The instantiated object is the virtual type \Afterpay\Afterpay\Gateway\Command\ValidateCheckoutDataCommand
    // And ends up validating the response with \Afterpay\Afterpay\Gateway\Response\Checkout\CheckoutItemsAmountValidationHandler
    protected \Magento\Payment\Gateway\Command\GatewayCommand $validateCheckoutDataCommand;

    protected \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;

    public function __construct(
        \Magento\Payment\Gateway\Command\GatewayCommand $validateCheckoutDataCommand,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory
    ) {
        $this->validateCheckoutDataCommand = $validateCheckoutDataCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        if ($quote->getPayment()->getMethod() !== \Afterpay\Afterpay\Gateway\Config\Config::CODE) {
            return;
        }

        $this->validateCheckoutDataCommand->execute(['payment' => $this->paymentDataObjectFactory->create($quote->getPayment())]);
    }
}
