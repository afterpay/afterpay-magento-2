<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Quote\Model\Quote\Payment;

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
    ) {
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->reversalCommand = $reversalCommand;
        $this->voidCommand = $voidCommand;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->quotePaidStorage = $quotePaidStorage;
    }

    /**
     * @param Payment         $payment
     * @param int             $quoteId
     * @param \Throwable|null $e
     *
     * @return void
     * @throws CommandException
     * @throws LocalizedException
     * @throws \Throwable
     */
    public function execute(\Magento\Quote\Model\Quote\Payment $payment, int $quoteId, \Throwable $e = null): void
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
                    $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_ORDER_ID)// @codingStandardsIgnoreLine
                )
            );
        }

        $afterpayPayment = $this->quotePaidStorage->getAfterpayPaymentIfQuoteIsPaid($quoteId);
        if (!$afterpayPayment) {
            if ($e instanceof LocalizedException) {
                throw $e;
            }

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
