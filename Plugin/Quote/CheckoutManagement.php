<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Quote;

use Afterpay\Afterpay\Model\Checks\PaymentMethodInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class CheckoutManagement
{
    private CartRepositoryInterface $quoteRepository;
    private Session $checkoutSession;
    private PaymentMethodInterface $paymentMethodChecker;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session                 $checkoutSession,
        PaymentMethodInterface  $paymentMethodChecker
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->paymentMethodChecker = $paymentMethodChecker;
    }

    public function beforePlaceOrder(CartManagementInterface $subject, $cartId, PaymentInterface $paymentMethod = null)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        $payment = $quote->getPayment();
        if ($this->paymentMethodChecker->isAfterPayMethod($payment) && !$this->checkoutSession->getAfterpayRedirect()) {
            throw new LocalizedException(__('You cannot use the chosen payment method.'));
        }

        return [$cartId, $paymentMethod];
    }
}
