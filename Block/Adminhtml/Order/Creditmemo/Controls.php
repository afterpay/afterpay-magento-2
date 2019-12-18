<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2019 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block\Adminhtml\Order\Creditmemo;

class Controls extends \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\Controls
{
    public function canRefundToCustomerBalance()
    {
		$creditMemo = $this->_coreRegistry->registry('current_creditmemo');
		$payment = $creditMemo->getOrder()->getPayment();
		if($payment->getMethod() == \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE ){
			return false;
		}
        return true;
    }
}
