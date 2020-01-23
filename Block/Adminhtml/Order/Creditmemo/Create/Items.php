<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block\Adminhtml\Order\Creditmemo\Create;

class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{
    protected function _prepareLayout()
    {
		parent::_prepareLayout();
		$payment = $this->getCreditmemo()->getOrder()->getPayment();
		if($payment->getMethod() == \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE ){
			$this->unsetChild(
                'submit_offline'
            );
		}
    }
}
