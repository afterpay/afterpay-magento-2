<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Model\Quote;

class PlaceOrderProcessor
{
    private $cartManagement;
    private $cancelOrderProcessor;
    private $quotePaidStorage;
    private $paymentDataObjectFactory;
    private $logger;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Afterpay\Afterpay\Model\Payment\Capture\CancelOrderProcessor $cancelOrderProcessor,
        \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage $quotePaidStorage,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->cancelOrderProcessor = $cancelOrderProcessor;
        $this->quotePaidStorage = $quotePaidStorage;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->logger = $logger;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $afterpayOrderToken): void
    {
        try {
            $quote->getPayment()->setAdditionalInformation(
                \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_TOKEN,
                $afterpayOrderToken
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
                        'There was a problem placing your order. Your Afterpay order %1 is refunded.',
                        $afterpayPayment->getAdditionalInformation(AdditionalInformationInterface::AFTERPAY_ORDER_ID)
                    )
                );
            }
            throw $e;
        }
    }
}
