<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Block\Adminhtml\Order\Creditmemo\Create;

class Items
{
    private $layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->layout = $layout;
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

        if ($payment->getMethod() == \Afterpay\Afterpay\Gateway\Config\Config::CODE) {
            $this->layout->unsetChild(
                $creditmemoBlock->getNameInLayout(),
                !$creditmemo->canRefund() ? 'submit_button' : 'submit_offline'
            );
        }
        return null;
    }
}
