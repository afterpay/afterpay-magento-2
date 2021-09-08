<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Afterpay\Afterpay\Helper\Data as Helper;

/**
 * Class BeforeShipment
 * @package Afterpay\Afterpay\Observer
 */
class BeforeShipment implements ObserverInterface
{ 
  protected $_helper;
  protected $_orderRepository;
  protected $_paymentCapture;
  protected $_afterpayResponse;
  protected $_jsonHelper;
  
  public function __construct(
	Helper $helper,
	\Magento\Sales\Model\OrderRepository $orderRepository,
	\Afterpay\Afterpay\Model\Adapter\V2\AfterpayOrderPaymentCapture $paymentCapture,
	\Afterpay\Afterpay\Model\Response $afterpayResponse,
	\Magento\Framework\Json\Helper\Data $jsonHelper
  )
  {
    $this->_helper = $helper;
	$this->_orderRepository = $orderRepository;
	$this->_paymentCapture = $paymentCapture;
	$this->_afterpayResponse = $afterpayResponse;
	$this->_jsonHelper = $jsonHelper;
  }

 public function execute(\Magento\Framework\Event\Observer $observer)
  {
	$shipment = $observer->getEvent()->getShipment();
	$order    = $shipment->getOrder();
	$payment  = $order->getPayment();  
	
	if($payment->getMethod() == \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE ){
		
		$afterpayPaymentStatus = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS);
		if($afterpayPaymentStatus == \Afterpay\Afterpay\Model\Response::PAYMENT_STATUS_AUTH_APPROVED || $afterpayPaymentStatus == \Afterpay\Afterpay\Model\Response::PAYMENT_STATUS_PARTIALLY_CAPTURED){
			
			$totalCaptureAmount  = 0.00;
			$totalItemsToShip    = 0;
			$openToCaptureAmount = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT); 
			$totalDiscountAmount = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT); 
			$rolloverAmount      = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT); 
			$rolloverRefund      = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND); 
			
			if($order->getShippingInclTax() > 0 && $order->getShipmentsCollection()->count()==0){
				$shippingAmount = $order->getShippingInclTax();
				
				if($order->getShippingRefunded() > 0)
				{
					$shippingAmount = $shippingAmount - ($order->getShippingRefunded() + $order->getShippingTaxRefunded());
				}
				$totalCaptureAmount = $totalCaptureAmount +  $shippingAmount;
			}

			if($rolloverAmount > 0){
				$totalCaptureAmount = $totalCaptureAmount + $rolloverAmount; 
				$payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT,"0.00");
			}			
		
			foreach($order->getItemsCollection() as $item){
				if (!$item->getParentItem() && !$item->getIsVirtual()) {
					$totalItemsToShip = $totalItemsToShip + $item->getQtyToShip();
				}
			}
			
			foreach($shipment->getItemsCollection() as $item) {
				if (!$item->getOrderItem()->getParentItem()) {
					$itemPrice = $this->_afterpayResponse->calculateItemPrice($item->getOrderItem(),$item->getQty());
					$totalCaptureAmount = $totalCaptureAmount + $itemPrice;
					$totalItemsToShip = $totalItemsToShip - $item->getQty();
				}
			}

			if($totalDiscountAmount!=0){
				if($totalCaptureAmount >= $totalDiscountAmount){
					$this->_helper->debug("totalDiscountAmount :  ".$totalDiscountAmount);
					$totalCaptureAmount = $totalCaptureAmount - $totalDiscountAmount;
					$totalDiscountAmount = 0.00;
				}
				else if($totalCaptureAmount < $totalDiscountAmount){
					$totalDiscountAmount = $totalDiscountAmount  - $totalCaptureAmount;
					$totalCaptureAmount = 0.00;
				}
				$payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT, number_format($totalDiscountAmount, 2, '.', ''));
			}
		
			
			if($totalCaptureAmount > 1){
				$afterpay_order_id = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);
				$merchant_order_id = $order->getIncrementId();
				$currencyCode      = $order->getOrderCurrencyCode();
				$override = ["website_id" => $payment->getOrder()->getStore()->getWebsiteId()];
				
				$totalAmount= [
							'amount'   => number_format($totalCaptureAmount, 2, '.', ''),
							'currency' => $currencyCode
						];
					
				//Capture payment
				$response = $this->_paymentCapture->send($totalAmount,$merchant_order_id,$afterpay_order_id,$override);
				$response = $this->_jsonHelper->jsonDecode($response->getBody());
				
				if(!array_key_exists("errorCode",$response)) {
					$payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS,$response['paymentState']);
					if(array_key_exists('openToCaptureAmount',$response) && !empty($response['openToCaptureAmount'])){
						$payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT,number_format($response['openToCaptureAmount']['amount'], 2, '.', ''));
					}
				}
				else{
					$this->_helper->debug("Transaction Exception : " . json_encode($response));
					throw new \Magento\Framework\Exception\LocalizedException(__($response['message']));
				}
			}
			else{
				if($totalCaptureAmount > 0){
					$payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT,$totalCaptureAmount);
					$this->_helper->debug("Total afterpay capture amount is less then $1 for this shipment. We are adding it to the 'rollover amount' field");
				}
			}
			//last shipment
			if($totalItemsToShip == 0 && $rolloverRefund > 0){
				$payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND,"0.00");
				$result = $this->_afterpayResponse->lastShipmentProcessRefund($payment,$rolloverRefund);
				if(!$result['success']){
					throw new \Magento\Framework\Exception\LocalizedException(__('There was a problem with your shipment. Please check the logs.'));
				}
			}
		}
	}
  }
}
?>