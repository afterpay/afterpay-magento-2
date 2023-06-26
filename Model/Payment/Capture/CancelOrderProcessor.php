<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

class CancelOrderProcessor
{
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Magento\Payment\Gateway\CommandInterface $voidCommand;
    private \Magento\Payment\Gateway\CommandInterface $reversalCommand;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private \Afterpay\Afterpay\Model\Config $config;
    private \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;

    public function __construct(
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Payment\Gateway\CommandInterface                       $voidCommand,
        \Magento\Payment\Gateway\CommandInterface                       $reversalCommand,
        \Magento\Store\Model\StoreManagerInterface                      $storeManager,
        \Afterpay\Afterpay\Model\Config                                 $config,
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage         $quotePaidStorage
    ) {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->voidCommand = $voidCommand;
        $this->reversalCommand = $reversalCommand;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->quotePaidStorage = $quotePaidStorage;
    }

    public function execute(\Magento\Quote\Model\Quote\Payment $payment, int $quoteId): void
    {
        if (!$this->config->getIsReversalEnabled()) {
            return;
        }

        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($payment)];

        if (!$this->isDeferredPaymentFlow()) {
            $this->reversalCommand->execute($commandSubject);

            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'There was a problem placing your order. Your Afterpay order %1 is refunded.',
                    $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ORDER_ID)
                )
            );
        }

        $afterpayPayment = $this->quotePaidStorage->getAfterpayPaymentIfQuoteIsPaid($quoteId);
        if (!$afterpayPayment) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Afterpay payment is declined. Please select an alternative payment method.'
                )
            );
        }

        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($afterpayPayment)];
        $this->voidCommand->execute($commandSubject);
    }

    private function isDeferredPaymentFlow(): bool
    {
        $websiteId = (int)$this->storeManager->getStore()->getWebsiteId();
        $paymentFlow = $this->config->getPaymentFlow($websiteId);

        return $paymentFlow === \Afterpay\Afterpay\Model\Config\Source\PaymentFlow::DEFERRED;
    }
}
