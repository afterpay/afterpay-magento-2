<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Block\Adminhtml\Order\Creditmemo\Create;

class Items
{
    private \Magento\Framework\View\LayoutInterface $layout;
    private \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod
    ) {
        $this->layout = $layout;
        $this->checkPaymentMethod = $checkPaymentMethod;
    }

    /**
     * @return null
     */
    public function beforeToHtml(
        \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items $creditmemoBlock
    ) {
        $creditmemo = $creditmemoBlock->getCreditmemo();
        $payment = $creditmemo->getOrder()->getPayment();
        if ($payment == null) {
            return null;
        }

        if ($this->checkPaymentMethod->isAfterPayMethod($payment)) {
            $this->layout->unsetChild(
                $creditmemoBlock->getNameInLayout(),
                !$creditmemo->canRefund() ? 'submit_button' : 'submit_offline'
            );
        }
        return null;
    }
}
