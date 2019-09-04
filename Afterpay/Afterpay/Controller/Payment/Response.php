<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2019 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Controller\Payment;

use \Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class Response
 * @package Afterpay\Afterpay\Controller\Payment
 */
class Response extends \Magento\Framework\App\Action\Action
{
    const DEFAULT_REDIRECT_PAGE = 'checkout/cart';

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $_resultForwardFactory;
    protected $response;
    protected $_helper;
    protected $_checkoutSession;
    protected $_jsonHelper;
    protected $_afterpayConfig;
    protected $_directCapture;
    protected $_tokenCheck;
    protected $_quoteManagement;
    protected $_transactionBuilder;
    protected $_orderSender;
    protected $_orderRepository;
    protected $_paymentRepository;
    protected $_transactionRepository;
	protected $_notifierPool;
	
    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Afterpay\Afterpay\Model\Response $response
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Afterpay\Afterpay\Model\Response $response,
        \Afterpay\Afterpay\Helper\Data $helper,
        \Afterpay\Afterpay\Model\Adapter\V1\AfterpayOrderDirectCapture $directCapture,
        \Afterpay\Afterpay\Model\Adapter\V1\AfterpayOrderTokenCheck $tokenCheck,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Afterpay\Afterpay\Model\Config\Payovertime $afterpayConfig,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
		\Magento\Framework\Notification\NotifierInterface $notifierPool
    ) {
        $this->_resultForwardFactory = $resultForwardFactory;
        $this->response = $response;
        $this->_helper = $helper;
        $this->_checkoutSession = $checkoutSession;

        $this->_jsonHelper = $jsonHelper;

        $this->_directCapture = $directCapture;

        $this->_tokenCheck = $tokenCheck;

        $this->_afterpayConfig = $afterpayConfig;
        
        $this->_quoteManagement = $quoteManagement;

        $this->_transactionBuilder = $transactionBuilder;

        $this->_orderSender = $orderSender;

        $this->_orderRepository = $orderRepository;

        $this->_paymentRepository = $paymentRepository;

        $this->_transactionRepository = $transactionRepository;
		
        $this->_notifierPool = $notifierPool;

        parent::__construct($context);
    }

    /**
     * Actual action when accessing url
     */
    public function execute()
    {
        // debug mode
        $this->_helper->debug('Start \Afterpay\Afterpay\Controller\Payment\Response::execute() with request ' . $this->_jsonHelper->jsonEncode($this->getRequest()->getParams()));

        $query = $this->getRequest()->getParams();
        $order = $this->_checkoutSession->getLastRealOrder();

        // Check if not fraud detected not doing anything (let cron update the order if payment successful)
        if ($this->_afterpayConfig->getPaymentAction() == AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            //Steven - Bypass the response and do capture
            $redirect = $this->_processAuthCapture($query);
        } elseif (!$this->response->validCallback($order, $query)) {
            $this->_helper->debug('Request redirect url is not valid.');
        }
        // debug mode
        $this->_helper->debug('Finished \Afterpay\Afterpay\Controller\Payment\Response::execute()');

        // Redirect to cart
        $this->_redirect($redirect);
    }

    private function _processAuthCapture($query)
    {
        $redirect = self::DEFAULT_REDIRECT_PAGE;
        try {
            switch ($query['status']) {
                case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_CANCELLED:
                    $this->messageManager->addError(__('You have cancelled your Afterpay payment. Please select an alternative payment method.'));
                    break;
                case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_FAILURE:
                    $this->messageManager->addError(__('Afterpay payment failure. Please select an alternative payment method.'));
                    break;
                case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_SUCCESS:
                    //Steven - Capture here
                    $quote = $this->_checkoutSession->getQuote();

                    $payment = $quote->getPayment();

                    $token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);
                    $merchant_order_id = $quote->getReservedOrderId();

                    $response_check = $this->_tokenCheck->generate($token);
                    $response_check = $this->_jsonHelper->jsonDecode($response_check->getBody());

                    /**
                     * Validation to check between session and post request
                     */
                    if (!$response_check) {
                        // Check the order token being use
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Token'));
                    } elseif ($merchant_order_id != $response_check['merchantReference']) {
                        // Check order id
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Merchant Reference'));
                    } elseif (round($quote->getGrandTotal(), 2) != round($response_check['totalAmount']['amount'], 2)) {
                        // Check the order amount
                        throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment. Invalid Amount'));
                    }


                    $response = $this->_directCapture->generate($token, $merchant_order_id);
                    $response = $this->_jsonHelper->jsonDecode($response->getBody());

                    if (empty($response['status'])) {
                        $response['status'] = \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_DECLINED;
                        $this->_helper->debug("Transaction Exception (Empty Response): " . json_encode($response));
                    }

                    switch ($response['status']) {
                        case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_APPROVED:
                            $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID, $response['id']);

                            $this->_checkoutSession
                                ->setLastQuoteId($quote->getId())
                                ->setLastSuccessQuoteId($quote->getId())
                                ->clearHelperData();

                            // Create Order From Quote
                            $quote->collectTotals();
                            //Catch the deadlock exception while creating the order and retry 3 times
                            
                            $tries = 0;
                            do
                            {
	                          $retry = false;
							  
	                          try{
								  $this->_helper->debug("Trying Order Creation. Try number:".$tries);
		                          $order = $this->_quoteManagement->submit($quote);
                              }
	                            catch (\Exception $e) {
								
		                         if (preg_match('/SQLSTATE\[40001\]: Serialization failure: 1213 Deadlock found/', $e->getMessage()) && $tries<2){
			                       $this->_helper->debug("Waiting for a second before retrying the Order Creation");
			                       $retry = true;
			                       sleep(1);
		                         } 
		                         else{
									$this->_notifierPool->addMajor(
									'Afterpay Order Failed',
									'There was a problem with an Afterpay order. Order number : '.$response['id'].' and the merchant order number : '.$merchant_order_id,
									''
									);
									$this->_helper->debug("Order Exception : There was a problem with order creation. ".$e->getMessage());
		                         }
	                            }
								$tries++;
                            }while($tries<3 && $retry);
                            // $order->setEmailSent(0);
                            if ($order) {
                                $this->_checkoutSession->setLastOrderId($order->getId())
                                                   ->setLastRealOrderId($order->getIncrementId())
                                                   ->setLastOrderStatus($order->getStatus());

                                $this->_createTransaction($order, $response);

                                //email sending mechanism
                                $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();
                                if (!$redirectUrl && $order->getCanSendNewEmailFlag()) {
                                    try {
                                        $this->_orderSender->send($order);
                                    } catch (\Exception $e) {
                                        $this->_helper->debug("Transaction Email Sending Error: " . json_encode($e));
                                    }
                                }


                                $this->messageManager->addSuccess("Afterpay Transaction Completed");

                                $redirect = 'checkout/onepage/success';
                                // $this->_redirect('checkout/onepage/success');
                            } else {
	                           $this->_helper->debug("Order Exception : There was a problem with order creation.");
                            }
                            break;
                        case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_DECLINED:
                            $this->messageManager->addError(__('Afterpay payment declined. Please select an alternative payment method.'));
                            break;
                        // case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_PENDING:
                        //     $payment->setTransactionId($payment->getAfterpayOrderId())
                        //         ->setIsTransactionPending(true);
                        //     break;
                        default:
                            // $this->messageManager->addError(__('There is a problem with your Afterpay payment. Please select an alternative payment method.'));
                            $this->messageManager->addError($response);
                            break;
                    }
                    break;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_helper->debug("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError(
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->_helper->debug("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError("There was a problem in placing your order.");
        }
        
        return $redirect;
    }

    private function _createTransaction($order = null, $paymentData = [])
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
 
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['id'])
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
 
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $this->_paymentRepository->save($payment);
            $this->_orderRepository->save($order);
            $transaction = $this->_transactionRepository->save($transaction);
 
            return  $transaction->getTransactionId();
        } catch (\Exception $e) {
            //log errors here
            $this->_helper->debug("Transaction Exception: There was a problem with creating the transaction. ".$e->getMessage());
        }
    }
}
