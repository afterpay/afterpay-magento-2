<?php
/**
 * Magento 2 extensions for Afterpay
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Mony https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Sales\Model\OrderFactory as OrderFactory;
use \Magento\Quote\Model\QuoteFactory as QuoteFactory;
use \Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;
use \Magento\Payment\Model\Method\AbstractMethod;
use \Afterpay\Afterpay\Model\Adapter\V1\AfterpayOrderTokenV1 as AfterpayOrderTokenV1;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Afterpay\Afterpay\Helper\Data as Helper;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Store\Model\StoreResolver as StoreResolver;

/**
 * Class Response
 * @package Afterpay\Afterpay\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_quoteFactory;
    protected $_afterpayConfig;
    protected $_afterpayOrderTokenV1;
    protected $_jsonHelper;
    protected $_helper;
    protected $_cart;
    protected $_storeResolver;

    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        AfterpayConfig $afterpayConfig,
        AfterpayOrderTokenV1 $afterpayOrderTokenV1,
        JsonHelper $jsonHelper,
        Helper $helper,
        Cart $cart,
        StoreResolver $storeResolver
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_afterpayConfig = $afterpayConfig;
        $this->_afterpayOrderTokenV1 = $afterpayOrderTokenV1;
        $this->_jsonHelper = $jsonHelper;
        $this->_helper = $helper;
        $this->_cart = $cart;
        $this->_storeResolver = $storeResolver;

        parent::__construct($context);
    }

    public function execute() {
        if( $this->_afterpayConfig->getPaymentAction() == AbstractMethod::ACTION_AUTHORIZE_CAPTURE ) {
            $this->_processAuthorizeCapture();
        }
        else {
            $this->_processOrder();
        } 
    }   

    public function _processAuthorizeCapture() {
        
        //need to load the correct quote by store
        $data = $this->_checkoutSession->getData();
        
        $quote = $this->_checkoutSession->getQuote();
        $store_id = $this->_afterpayConfig->getStoreObjectFromRequest()->getId();

        if( $store_id > 1 ) {
            $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($data["quote_id_" . $store_id]);    
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // customer login
            $quote->setCustomer($customer);

            $billingAddress  = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();

            //check if shipping address is missing - e.g. Gift Cards
            if( empty($shippingAddress) || empty($shippingAddress->getStreetLine(1)) && empty($billingAddress) || empty($billingAddress->getStreetLine(1))  ) {
                die( json_encode( array("success" => false, "message" => "Please select an Address") ) );
            }
            else if( empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))  || empty($shippingAddress->getFirstname()) ) {
                $shippingAddress = $quote->getBillingAddress();
                $quote->setShippingAddress($object->getBillingAddress());
            }
            else if( empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname()) ) {
                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($object->getShippingAddress());
            }
        }
        else {
            $post = $this->getRequest()->getPostValue();

            if( !empty($post['email']) ) {
                $quote->setCustomerEmail($post['email'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
            }
        }

        $payment = $quote->getPayment();

        $payment->setMethod(\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE);

        $quote->reserveOrderId();


        try {
            $payment = $this->_getAfterPayOrderToken($this->_afterpayOrderTokenV1, $payment, $quote);
        }
        catch (\Exception $e) {
            die( json_encode( array('error' => 1, 'message' => $e->getMessage()) ) );
        }

        $quote->setPayment($payment);
        $quote->save();

        $this->_checkoutSession->setQuote($quote);

        $token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);

        die( json_encode( array("success" => true, "token" => $token) ) );
    }

    /**
     * @param $payment
     * @return bool
     * @throws LocalizedException
     */
    private function _getAfterPayOrderToken($afterpayOrderToken, $payment, $targetObject)
    {
        if( $targetObject && $targetObject->getReservedOrderId() ) {
            $result = $afterpayOrderToken->generate($targetObject, \Afterpay\Afterpay\Model\Payovertime::AFTERPAY_PAYMENT_TYPE_CODE_V1, array('merchantOrderId' => $targetObject->getReservedOrderId() ) );
        }
        else if( $targetObject ) {
            $result = $afterpayOrderToken->generate($targetObject, \Afterpay\Afterpay\Model\Payovertime::AFTERPAY_PAYMENT_TYPE_CODE_V1);
        }
        
        $result = $this->_jsonHelper->jsonDecode($result->getBody(), true);
        $orderToken = array_key_exists('token', $result) ? $result['token'] : false;

        if ($orderToken) {
            $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN, $orderToken);
        } else {
            $this->_helper->debug('No Token response from API');
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue processing your order.'));
        }
        return $payment;
    }

    private function _processOrder() {
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order = $this->_orderFactory->create()->load($orderId);

        $payment = $order->getPayment();

        if( !empty($payment) ) {
            $token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);
            die( json_encode( array("success" => true, "token" => $token) ) );
        }
        else {
            die( json_encode( array("success" => false) ) );
        }
    }
}