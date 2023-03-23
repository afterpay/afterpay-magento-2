<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

class CancelOrderProcessor
{
    private $paymentDataObjectFactory;
    private $reversalCommand;
    private $voidCommand;
    private $storeManager;
    private $config;
    private $quotePaidStorage;

    public function __construct(
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Magento\Payment\Gateway\CommandInterface                       $reversalCommand,
        \Magento\Payment\Gateway\CommandInterface                       $voidCommand,
        \Magento\Store\Model\StoreManagerInterface                      $storeManager,
        \Afterpay\Afterpay\Model\Config                                 $config,
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage         $quotePaidStorage
    )
    {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->reversalCommand = $reversalCommand;
        $this->voidCommand = $voidCommand;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->quotePaidStorage = $quotePaidStorage;
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Quote\Model\Quote\Payment $payment, int $quoteId): void
    {
        $commandSubject = ['payment' => $this->paymentDataObjectFactory->create($payment)];

        if (!$this->isDeferredPaymentFlow()) {
            $this->reversalCommand->execute($commandSubject);

            return;
        }

        $afterpayPayment = $this->quotePaidStorage->getAfterpayPaymentIfQuoteIsPaid($quoteId);
        if (!$afterpayPayment) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Afterpay payment declined. Please select an alternative payment method.'
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
