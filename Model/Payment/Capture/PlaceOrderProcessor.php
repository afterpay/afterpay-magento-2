<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Model\Quote;

class PlaceOrderProcessor
{
    private \Magento\Quote\Api\CartManagementInterface $cartManagement;
    private \Afterpay\Afterpay\Model\Payment\Capture\CancelOrderProcessor $cancelOrderProcessor;
    private \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage;
    private \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Afterpay\Afterpay\Model\Payment\Capture\CancelOrderProcessor $cancelOrderProcessor,
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface  $checkCBTCurrencyAvailability,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->cancelOrderProcessor = $cancelOrderProcessor;
        $this->quotePaidStorage = $quotePaidStorage;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
        $this->logger = $logger;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $afterpayOrderToken): void
    {
        try {
            $quote->getPayment()->setAdditionalInformation(
                \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN,
                $afterpayOrderToken
            );

            $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
            $quote->getPayment()->setAdditionalInformation(
                \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY,
                $isCBTCurrencyAvailable
            );
            $quote->getPayment()->setAdditionalInformation(
                \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_CBT_CURRENCY,
                $quote->getQuoteCurrencyCode()
            );

            if (!$quote->getCustomerId()) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
            }

            $checkoutDataCommand->execute(['payment' => $this->paymentDataObjectFactory->create($quote->getPayment())]);

            $this->cartManagement->placeOrder($quote->getId());
        } catch (\Throwable $e) {
            $this->logger->critical('Order placement is failed with error: ' . $e->getMessage());
            $quoteId = (int)$quote->getId();
            if ($afterpayPayment = $this->quotePaidStorage->getAfterpayPaymentIfQuoteIsPaid($quoteId)) {
                $this->cancelOrderProcessor->execute($afterpayPayment);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(
                        'There was a problem placing your order. Your %1 order %2 is refunded.',
                        $quote->getPayment()->getMethodInstance()->getTitle(),
                        $afterpayPayment->getAdditionalInformation(AdditionalInformationInterface::AFTERPAY_ORDER_ID)
                    )
                );
            }
            throw $e;
        }
    }
}
