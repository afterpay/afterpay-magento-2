<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Block\Adminhtml\CustomerBalance\Order\Creditmemo;

class Controls
{
    private \Magento\Framework\Registry $registry;
    private \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod
    ) {
        $this->registry = $registry;
        $this->checkPaymentMethod = $checkPaymentMethod;
    }

    /**
     * @param \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\Controls $subject
     * @param callable $proceed
     *
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCanRefundToCustomerBalance($subject, callable $proceed)
    {
        $creditmemo = $this->registry->registry('current_creditmemo');
        if (!$creditmemo) {
            return $proceed();
        }
        $payment = $creditmemo->getOrder()->getPayment();
        if (!$payment) {
            return $proceed();
        }
        if ($this->checkPaymentMethod->isAfterPayMethod($payment)) {
            return false;
        }
        return $proceed();
    }
}
