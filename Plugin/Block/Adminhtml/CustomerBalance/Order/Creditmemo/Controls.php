<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Block\Adminhtml\CustomerBalance\Order\Creditmemo;

class Controls
{
    private $registry;

    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
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
        if ($payment->getMethod() == \Afterpay\Afterpay\Gateway\Config\Config::CODE) {
            return false;
        }
        return $proceed();
    }
}
