<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteFactory as QuoteFactory;
use Afterpay\Afterpay\Model\Adapter\V2\AfterpayOrderTokenV2 as AfterpayOrderTokenV2;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Afterpay\Afterpay\Helper\Data as Helper;
use Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use Magento\Quote\Model\Cart\ShippingMethodConverter as Converter;

class AfterpayExpressPayment
{
    protected $_checkoutSession;
    
    protected $_orderRepository;
    
    protected $_quoteFactory;
    
    protected $_afterpayOrderTokenV2;
    
    protected $_jsonHelper;
    
    protected $_helper;
    
    protected $_quoteRepository;
    
    protected $_totalsCollector;
    
    protected $_paymentCapture;
    
    protected $_transactionRepository;
    
    protected $_objectManager;
    
    protected $_transactionBuilder;
    
    protected $_paymentRepository;
    
    /**
     * Shipping method converter
     *
     * @var \Magento\Quote\Model\Cart\ShippingMethodConverter
     */
    protected $_converter;
    
    /**
     * @var Payovertime $_payOverTime
     */
    protected $_payOverTime;
    /**
     * Response constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct( 
        CheckoutSession $checkoutSession,         
        \Magento\Sales\Model\OrderRepository $orderRepository,
        QuoteFactory $quoteFactory,  
        AfterpayOrderTokenV2 $afterpayOrderTokenV2, 
        JsonHelper $jsonHelper,
        Helper $helper,
        QuoteRepository $quoteRepository, 
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector, 
        Converter $converter, 
        \Afterpay\Afterpay\Model\Adapter\V2\AfterpayOrderPaymentCapture $paymentCapture, 
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,        
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Afterpay\Afterpay\Model\Config\Payovertime $payovertime
        )
    {
        $this->_checkoutSession = $checkoutSession;        
        $this->_orderRepository = $orderRepository;
        $this->_quoteFactory = $quoteFactory;
        $this->_afterpayOrderTokenV2 = $afterpayOrderTokenV2;
        $this->_jsonHelper = $jsonHelper;
        $this->_helper = $helper;
        $this->_quoteRepository = $quoteRepository;
        $this->_totalsCollector = $totalsCollector;
        $this->_converter = $converter;
        $this->_paymentCapture = $paymentCapture;
        $this->_transactionRepository = $transactionRepository;
        $this->_objectManager = $objectManager;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_paymentRepository = $paymentRepository;
        $this->_payOverTime = $payovertime;
       
    }
    /**
     *
     * @param $afterpayOrderToken
     * @param $payment
     * @param $targetObject
     * @return bool
     * @throws LocalizedException
     *
     */
    public function getAfterPayExpressOrderToken($afterpayOrderToken, $payment, $targetObject)
    {
        if ($targetObject && $targetObject->getReservedOrderId()) {
            $result = $afterpayOrderToken->generate($targetObject, [
                'merchantOrderId' => $targetObject->getReservedOrderId(),
                'mode' => \Afterpay\Afterpay\Model\Config\Source\CartMode::EXPRESS_CHECKOUT
            ]);
        } elseif ($targetObject) {
            $result = $afterpayOrderToken->generate($targetObject, [
                'mode' => \Afterpay\Afterpay\Model\Config\Source\CartMode::EXPRESS_CHECKOUT
            ]);
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
    
    /**
     * Get Region Id
     *
     * @param string $stateCode
     * @param string $countryCode
     * @return int
     */
    public function getRegionId($stateCode, $countryCode)
    {
        $region = $this->_objectManager->create('Magento\Directory\Model\Region');
        
        return $region->loadByCode($stateCode, $countryCode)->getId();
    }
    
    /**
     * Get list of available shipping methods
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     */

    public function getShippingDetails($quote)
    {
        $output = [];
        if (! $quote->isVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true);
            
            $this->_totalsCollector->collectAddressTotals($quote, $shippingAddress);
            $shippingRates = $shippingAddress->getGroupedAllShippingRates();
            foreach ($shippingRates as $carrierRates) {
                foreach ($carrierRates as $rate) {
                    $output[] = $this->_converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
                }
            }
        }
        return $output;
        
    }
    
    
    
    /**
     * Format amount upto 2 decimal
     *
     * @param
     *            $amount
     * @return float
     */
    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }
    
    /*
     * Is cart Updated
     * @param \Magento\Quote\Model\Quote $quoteData
     * @param Array $responseItems
     *
     * @return bool
     */
    public function isCartUpdated($quoteData, $responseItems)
    {
        $isCartupdated = false;
        $quoteItems = $quoteData->getAllItems();
        if ($quoteData->getItemsCount()!= count($responseItems)) {
            $isCartupdated = true;
            $this->_helper->debug('Cart Items count does not match. Quote Count : '.$quoteData->getItemsCount().' & Response count : '.count($responseItems));
        } else {
            foreach ($quoteItems as $items) {
                $itemFound = array_search($items->getSku(), array_column($responseItems, 'sku'));
                if ($itemFound === false) {
                    $this->_helper->debug('Cart Items '.$items->getSku().' does not match.');
                    $isCartupdated = true;
                    break;
                }
                continue;
            }
        }
        return $isCartupdated;
    }
    
    /**
     * Update Order databased on response
     */
    public function setOrderData($data)
    {
        $quote = $this->_checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        
        // Set first name & lastname in shipping address
        $fullName = explode(' ', $data['shipping']['name']);
        $lastName = array_pop($fullName);
        if (count($fullName) == 0) {
            // if $order['shipping']['name'] contains only one word
            $firstName = $lastName;
        } else {           
            $firstName = implode(' ', $fullName);
        }
        
        // Set Customer Data
        $customerSession = $this->_objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $this->_objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
        
        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);
            
            // customer login
            $quote->setCustomer($customer);
            
            // Set Billing Details
            if (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $billingID = $customerSession->getCustomer()->getDefaultBilling();
                $this->_helper->debug("No billing address found. Adding the Customer's default billing address.");
                $address = $this->_objectManager->create('Magento\Customer\Model\Address')->load($billingID);
                $billingAddress->addData($address->getData());
            }
        } else {
            
            $quote->setCustomerEmail($data['consumer']['email'])
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID)
            ->setCustomerFirstname($data['consumer']['givenNames'])
            ->setCustomerLastname($data['consumer']['surname']);
            
            // Set Billing Details
            
            $billingAddress->setFirstname($firstName)
            ->setLastname($lastName)
            ->setEmail($data['consumer']['email'])
            ->setTelephone($data['shipping']['phoneNumber'])
            ->setStreet(array(
                $data['shipping']['line1'],
                isset($data['shipping']['line2']) ? $data['shipping']['line2'] : null
            ))
            ->setCity($data['shipping']['area1'])
            ->setRegion($data['shipping']['region'])
            ->setRegionId($this->getRegionId($data['shipping']['region'], $data['shipping']['countryCode']))
            ->setPostcode($data['shipping']['postcode'])
            ->setCountryId($data['shipping']['countryCode']);
            
            // Set flag for same billing and shipping address
            $shippingAddress->setSameAsBilling(1);
        }
        
        // Set Shipping Details
        if (! $quote->isVirtual()) {
            $shippingAddress->setFirstname($firstName)
            ->setLastname($lastName)
            ->setEmail($data['consumer']['email'])
            ->setTelephone($data['shipping']['phoneNumber'])
            ->setStreet(array(
                $data['shipping']['line1'],
                isset($data['shipping']['line2']) ? $data['shipping']['line2'] : null
            ))
            ->setCity($data['shipping']['area1'])
            ->setRegion($data['shipping']['region'])
            ->setRegionId($this->getRegionId($data['shipping']['region'], $data['shipping']['countryCode']))
            ->setPostcode($data['shipping']['postcode'])
            ->setCountryId($data['shipping']['countryCode'])
            ->setShippingMethod($data['shippingOptionIdentifier'])
            ->setAddressType('shipping')
            ->setPaymentMethod(\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE);
        } else {
            $billingAddress->setPaymentMethod(\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE);
        }
        
        $quote->collectTotals();
        
        $this->_quoteRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);
    }
    
    /*
     * Calculate Total Discount for the given order
     */
    public function calculateTotalDiscount($order)
    {
        $storeCredit = $order->getCustomerBalanceAmount();
        $giftCardAmount = $order->getGiftCardsAmount();
        $totalDiscountAmount = $storeCredit + $giftCardAmount;
        return number_format($totalDiscountAmount, 2, '.', '');
    }
    
    /*
     * Capture payment for Virtal products
     */
    public function captureVirtual($order = null, $payment = null)
    {
        $totalCaptureAmount = 0.00;
        
        foreach ($order->getAllItems() as $items) {
            if ($items->getIsVirtual()) {
                $itemPrice = ($items->getQtyOrdered() * $items->getPrice()) + $items->getBaseTaxAmount();
                $totalCaptureAmount = $totalCaptureAmount + ($itemPrice - $items->getDiscountAmount());
            }
        }
        
        $totalDiscountAmount = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT);
        
        if ($totalDiscountAmount != 0) {
            if ($totalCaptureAmount >= $totalDiscountAmount) {
                $totalCaptureAmount = $totalCaptureAmount - $totalDiscountAmount;
                $totalDiscountAmount = 0.00;
            } else if ($totalCaptureAmount < $totalDiscountAmount) {
                $totalDiscountAmount = $totalDiscountAmount - $totalCaptureAmount;
                $totalCaptureAmount = 0.00;
            }
            $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT, number_format($totalDiscountAmount, 2, '.', ''));
        }
        
        if ($totalCaptureAmount >= 1) {
            $afterpay_order_id = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);
            $merchant_order_id = $order->getIncrementId();
            $currencyCode = $order->getOrderCurrencyCode();
            
            $totalAmount = [
                'amount' => number_format($totalCaptureAmount, 2, '.', ''),
                'currency' => $currencyCode
            ];
            
            $response = $this->_paymentCapture->send($totalAmount, $merchant_order_id, $afterpay_order_id);
            $response = $this->_jsonHelper->jsonDecode($response->getBody());
            
            if (! array_key_exists("errorCode", $response)) {
                $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS, $response['paymentState']);
                if (array_key_exists('openToCaptureAmount', $response) && ! empty($response['openToCaptureAmount'])) {
                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT, $response['openToCaptureAmount']['amount']);
                }
            } else {
                $this->_helper->debug("_captureVirtual : Transaction Exception : " . json_encode($response));
            }
        } else {
            if ($totalCaptureAmount < 1 && $totalCaptureAmount > 0) {
                $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT, number_format($totalCaptureAmount, 2, '.', ''));
            }
        }
    }
    
    /*
     * Create Transaction
     */
    public function createTransaction($order = null, $paymentData = [], $payment = null)
    {
        try {
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
            
            $message = __('The authorized amount is %1.', $formatedPrice);
            // get the object of builder class
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['id'])
            ->setFailSafe(true)
            ->
            // build method creates the transaction and returns the object
            build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
            
            $payment->addTransactionCommentsToOrder($transaction, $message);
            $payment->setParentTransactionId(null);
            $this->_paymentRepository->save($payment);
            
            $order->setBaseCustomerBalanceInvoiced(null);
            $order->setCustomerBalanceInvoiced(null);
            $this->_orderRepository->save($order);
            
            $transaction = $this->_transactionRepository->save($transaction);
            return $transaction->getTransactionId();
        } catch (\Exception $e) {
            // log errors here
            $this->_helper->debug("_createTransaction : Transaction Exception: There was a problem with creating the transaction. " . $e->getMessage());
        }
    }  
    
    /*
     * Validate order amount
     */
    public function isValidOrderAmount($orderAmount)
    {
        $max_limit=$this->formatAmount($this->_payOverTime->getMaxOrderLimit());
        $min_limit= $this->formatAmount($this->_payOverTime->getMinOrderLimit());
        $orderTotal=$this->formatAmount($orderAmount);
        return ($orderTotal<=$max_limit && $orderTotal>=$min_limit);
        
    }
   
}
