<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment\Capture;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface;
use Afterpay\Afterpay\Model\Order\Payment\Auth\TokenValidator;
use Afterpay\Afterpay\Model\Payment\PaymentErrorProcessor;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;

class PlaceOrderProcessor
{
    private CartManagementInterface $cartManagement;
    private PaymentDataObjectFactoryInterface $paymentDataObjectFactory;
    private CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;
    private PaymentErrorProcessor $paymentErrorProcessor;
    private TokenValidator $tokenValidator;

    public function __construct(
        CartManagementInterface               $cartManagement,
        PaymentDataObjectFactoryInterface     $paymentDataObjectFactory,
        CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        PaymentErrorProcessor                 $paymentErrorProcessor,
        TokenValidator                        $tokenValidator
    ) {
        $this->cartManagement = $cartManagement;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
        $this->paymentErrorProcessor = $paymentErrorProcessor;
        $this->tokenValidator = $tokenValidator;
    }

    public function execute(Quote $quote, CommandInterface $checkoutDataCommand, string $afterpayOrderToken): void
    {
        if ($this->tokenValidator->checkIsUsed($afterpayOrderToken)) {
            return;
        }

        $payment = $quote->getPayment();
        try {
            $payment->setAdditionalInformation(CheckoutInterface::AFTERPAY_TOKEN, $afterpayOrderToken);
            $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
            $payment->setAdditionalInformation(CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY, $isCBTCurrencyAvailable);
            $payment->setAdditionalInformation(CheckoutInterface::AFTERPAY_CBT_CURRENCY, $quote->getQuoteCurrencyCode());

            if (!$quote->getCustomerId()) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            }

            $checkoutDataCommand->execute(['payment' => $this->paymentDataObjectFactory->create($payment)]);
            $this->cartManagement->placeOrder($quote->getId());
        } catch (\Throwable $e) {
            $this->paymentErrorProcessor->execute($quote, $e, $payment);
        }
    }
}
