<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Afterpay\Afterpay\Helper\Data as Helper;

/**
 * Class BeforeCreditmemoLoad
 * @package Afterpay\Afterpay\Observer
 */
class BeforeCreditmemoLoad implements ObserverInterface
{ 
  protected $_helper;
  protected $_layout;
  protected $_registry;
  
  public function __construct(
	Helper $helper,
	\Magento\Framework\View\LayoutInterface $layout,
	\Magento\Framework\Registry $registry
  )
  {
    $this->_helper = $helper;
	$this->_layout = $layout;
	$this->_registry = $registry;
  }

 public function execute(\Magento\Framework\Event\Observer $observer)
  {
	$block = $observer->getEvent()->getBlock();
	$layout = $block->getLayout();

	if($layout->hasElement('sales_creditmemo_create')){
		$creditmemo = $this->_registry->registry('current_creditmemo');
		if($creditmemo){
			$order      = $creditmemo->getOrder();
			$payment    = $order->getPayment();
			
			if($payment->getMethod() == \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE ){
				$afterpayPaymentStatus = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS);
				if($afterpayPaymentStatus == \Afterpay\Afterpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED || $afterpayPaymentStatus == \Afterpay\Afterpay\Model\Response::PAYMENT_STATUS_PARTIALLY_CAPTURED){
					$block->unsetChild(
						'submit_offline'
					);
					if($layout->hasElement('customerbalance.creditmemo')){
						$layout->unsetElement('customerbalance.creditmemo');
					}
				}
			}
		}
	}
  }
}
?>