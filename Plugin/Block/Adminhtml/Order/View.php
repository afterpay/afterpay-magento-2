<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Block\Adminhtml\Order;

class View
{
    private $ckeckPaymentMethod;

    public function __construct(\Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $ckeckPaymentMethod)
    {
        $this->ckeckPaymentMethod = $ckeckPaymentMethod;
    }

    /**
     * @param string $buttonId
     * @param \Magento\Sales\Block\Adminhtml\Order\View $result
     * @return \Magento\Sales\Block\Adminhtml\Order\View
     */
    public function afterAddButton(
        \Magento\Sales\Block\Adminhtml\Order\View $orderView,
        $result,
        $buttonId
    ) {
        if ($buttonId !== 'order_creditmemo') {
            return $result;
        }
        $order = $orderView->getOrder();
        $payment = $order->getPayment();
        if ($payment == null) {
            return $result;
        }
        if ($this->ckeckPaymentMethod->isAfterPayMethod($payment)) {
            $orderView->removeButton($buttonId);
        }
        return $result;
    }
}
