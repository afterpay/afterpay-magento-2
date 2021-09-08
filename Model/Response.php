<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model;

/**
 * Class Response
 * @package Afterpay\Afterpay\Model
 */
class Response
{
    /**
     * constant variable
     */
    const RESPONSE_STATUS_SUCCESS   = 'SUCCESS';
    const RESPONSE_STATUS_CANCELLED = 'CANCELLED';
    const RESPONSE_STATUS_FAILURE   = 'FAILURE';

    /* Order payment statuses */
    const RESPONSE_STATUS_APPROVED = 'APPROVED';
    const RESPONSE_STATUS_PENDING  = 'PENDING';
    const RESPONSE_STATUS_FAILED   = 'FAILED';
    const RESPONSE_STATUS_DECLINED = 'DECLINED';

	const PAYMENT_STATUS_AUTH_APPROVED = 'AUTH_APPROVED';
	const PAYMENT_STATUS_CAPTURED = 'CAPTURED';
	const PAYMENT_STATUS_PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';
	const PAYMENT_STATUS_VOIDED = 'VOIDED';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    protected $checkoutSession;
    protected $request;
    protected $orderService;
    protected $invoiceService;
    protected $transactionFactory;
    protected $afterpayApiPayment;
    protected $helper;
    protected $jsonHelper;
    protected $salesOrderConfig;
    protected $status;
    protected $_orderRepository;
    protected $_paymentRepository;
    protected $_transactionRepository;
    protected $_quoteRepository;
    protected $paymentCapture;

    /**
     * Response constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param Adapter\AfterpayPayment $afterpayApiPayment
     * @param \Afterpay\Afterpay\Model\Adapter\AfterpayPayment $afterpayApiPayment
     * @param \Afterpay\Afterpay\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Sales\Model\Order\Config $salesOrderConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Order\Payment\Repository $paymentRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteRepository
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Afterpay\Afterpay\Model\Adapter\AfterpayPayment $afterpayApiPayment,
        \Afterpay\Afterpay\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Sales\Model\Order\Config $salesOrderConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
        \Magento\Quote\Model\ResourceModel\Quote $quoteRepository,
		\Afterpay\Afterpay\Model\Adapter\V2\AfterpayOrderPaymentCapture $paymentCapture
    ) {
        $this->objectManager = $objectManager;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->afterpayApiPayment = $afterpayApiPayment;
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        $this->salesOrderConfig = $salesOrderConfig;
        $this->_orderRepository = $orderRepository;
        $this->_paymentRepository = $paymentRepository;
        $this->_quoteRepository = $quoteRepository;
        $this->paymentCapture = $paymentCapture;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $response
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validCallback(\Magento\Sales\Model\Order $order, $response = [])
    {
        // check if no order given and no response status
        if (!array_key_exists('status', $response) || !array_key_exists('entity_id', $order->getData())) {
            return false;
        }

        // check if request not same as session i.e detetcted fraud
        $additionalInfo = $order->getPayment()->getAdditionalInformation();
        if ($this->request->getParam('orderToken') ===  $additionalInfo[\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN]) {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool $comment
     * @return $this
     */
    public function cancelOrder(\Magento\Sales\Model\Order $order, $comment = false)
    {
        if (!$order->isCanceled() &&
            $order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE &&
            $order->getState() !== \Magento\Sales\Model\Order::STATE_CLOSED) {
            // perform this before order process or cancel
            $this->_beforeUpdateOrder($order);

            // perform adding comment
            if ($comment) {
                $order->addStatusHistoryComment($comment);
            }

            // then canceling it
            $order->cancel();
            $this->_orderRepository->save($order);

            // debug mode
            $this->helper->debug('Cancel order for Magento order ' . $order->getIncrementId());
        }
        return $this;
    }

    /**
     * Return product to cart
     *
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    public function returnProductsToCart(\Magento\Sales\Model\Order $order)
    {
        //$quote = $this->objectManager->create('Magento\Quote\Model\Quote')->load($order->getQuoteId());
        $quote = $this->objectManager->create('Magento\Quote\Model\QuoteRepository')->get($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1)->setReservedOrderId(null);
            $this->_quoteRepository->save($quote);
            $this->checkoutSession->replaceQuote($quote);

            // debug mode
            $this->helper->debug('Reactivate cart session for order ' . $order->getIncrementId());
        }
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws bool
     */
    public function processSuccessPayment(\Magento\Sales\Model\Order $order, $orderId)
    {
        // if order has been process and possible timeout on first request
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            return true;
        }

        $this->_beforeUpdateOrder($order);

        // make sure the order can be invoiced and correct order
        if ($order->canInvoice() && $this->_shouldInvoiced($order, $orderId)) {
            // adding order ID to payment and last transaction Id
            $this->updatePayment($order, $orderId);

            // only approved can create invoice
            switch ($this->status) {
                case \Afterpay\Afterpay\Model\Status::STATUS_APPROVED:
                    // create invoice and update order
                    $this->createInvoiceAndUpdateOrder($order, $orderId);
                    break;
                case \Afterpay\Afterpay\Model\Status::STATUS_PENDING:
                    $order->addStatusHistoryComment(__('Payment under review by Afterpay'));
                    $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
                    $order->setStatus('payment_review');
                    $this->_orderRepository->save($order);
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * On processing or canceling the order, payment_review cannot be changed.
     * Perform this task first before processing or canceling the order
     *
     * @param $order
     * @return $this
     */
    protected function _beforeUpdateOrder($order)
    {
        // change the order status if payment review
        if ($order->isPaymentReview()) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus('pending_payment');
        }
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderId
     */
    public function updatePayment(\Magento\Sales\Model\Order $order, $orderId)
    {
        // adding Afterpay order id to the payment
        $payment = $order->getPayment();
        $payment->setTransactionId($orderId);
        $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID, $orderId);
        // have save here to link afterpay order id right after checking the API
        $this->_paymentRepository->save($payment);

        // debug mode
        $this->helper->debug('Added Afterpay Payment ID ' . $orderId . ' for Magento order ' . $order->getIncrementId());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws bool
     */
    public function createInvoiceAndUpdateOrder(\Magento\Sales\Model\Order $order, $orderId)
    {
        /**
         * Set the state of order to be processing, run in transaction along with creating invoice
         * Making sure the order won't change to processing if invoice not created.
         *
         * So then, cron will handle this gracefully.
         */
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->setStatus($this->salesOrderConfig->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING));

        $order->addStatusHistoryComment(__('Payment approved by Afterpay'));

        // prepare invoice and generate it
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE); // set to be capture offline because the capture has been done previously
        $invoice->register();

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order)
            ->addObject($invoice)
            ->addObject($invoice->getOrder())->save();

        // debug mode
        $this->helper->debug('Invoice created and update status for Magento order ' . $order->getIncrementId());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function _shouldInvoiced(\Magento\Sales\Model\Order $order, $afterpayOrderId)
    {
        // if already has invoice
        if ($order->hasInvoices()) {
            return false;
        }

        // only process afterpay method
        if ($order->getPayment()->getMethod() !== \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE) {
            return false;
        }

        // checking with API to make sure the payment exist with correct status in API
        $response = $this->afterpayApiPayment->getPayment($afterpayOrderId);
        $response = $this->jsonHelper->jsonDecode($response->getBody());

        $this->status = $response['status'];

        if ($response['token'] == $order->getPayment()->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN) &&
            ($response['status'] == \Afterpay\Afterpay\Model\Status::STATUS_APPROVED || $response['status'] == \Afterpay\Afterpay\Model\Status::STATUS_PENDING)
        ) {
            return true;
        }

        return false;
    }
	/**
     * @param InfoInterface $payment
     * @param float $amount
     * @return array
     */
    public function calculateRefund($payment, $amount)
    {
        $afterpayRefund   = false;
        $afterpayVoid     = false;
        $result           = [];
        $override         = [];
        $orderId          = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);

        if($orderId) {

            $order           = $payment->getOrder();
            $creditmemo      = $payment->getCreditmemo();
            $amountToCapture = 0.00;
            $storeCredit     = $creditmemo->getCustomerBalanceAmount();
            $override        = ["website_id" => $order->getStore()->getWebsiteId()];

            $afterpayPaymentStatus = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS);

            if($afterpayPaymentStatus == self::PAYMENT_STATUS_CAPTURED){
                $afterpayRefund = true;
            }
            elseif($afterpayPaymentStatus == self::PAYMENT_STATUS_PARTIALLY_CAPTURED || $afterpayPaymentStatus == self::PAYMENT_STATUS_AUTH_APPROVED){

                $orderTotal                = $order->getGrandTotal();
                $shippingApplied           = $creditmemo->getShippingInclTax();
                $adjustmentPositive        = $creditmemo->getAdjustmentPositive();
                $adjustmentNegative        = $creditmemo->getAdjustmentNegative();
                $amountCaptured            = 0.00;
                $amountNotCaptured         = 0.00;
                $amountToRefund            = 0.00;
                $openToCaptureAmount       = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT);
                $rolloverAmount            = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT);
                $rolloverRefund            = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND);
                $refundAmountAvailable     = $orderTotal - $openToCaptureAmount;
                $appliedDiscount           = 0.00;
                $refundedDiscount          = 0.00;
                $orderDiscount             = $order->getCustomerBalanceAmount() + $order->getGiftCardsAmount();
                $rolloverDiscount          = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT);
                $capturedDiscount          = $orderDiscount - $rolloverDiscount;
                $orderShippingAmount       = $order->getShippingInclTax();
                $actualOpenToCaptureAmount = $openToCaptureAmount - ($rolloverRefund + $rolloverAmount);
                $shippingRefunded          = ($order->getShippingRefunded() + $order->getShippingTaxRefunded()) - $shippingApplied;

                if($orderDiscount > 0){
                    $refundedDiscount = $order->getCustomerBalanceRefunded() + $order->getGiftCardsRefunded();
                    $appliedDiscount  = $creditmemo->getCustomerBalanceAmount() + $creditmemo->getGiftCardsAmount();
                }

                foreach ($creditmemo->getAllItems() as $item) {
                    $orderItem = $item->getOrderItem();
                    if (!$orderItem->getHasChildren()) {
                        $qtyToRefund    = $item->getQty();

                        if($orderItem->getIsVirtual()){
                            $amountCaptured = $amountCaptured + $this->calculateItemPrice($orderItem,$qtyToRefund);
                        }
                        else{
                            $qtyShipped     = $orderItem->getQtyShipped();
                            $qtyOrdered     = $orderItem->getQtyOrdered();
                            $QtyRefunded    = $orderItem->getQtyRefunded() - $qtyToRefund;
                            $itemLeftToShip = $qtyOrdered - ($qtyShipped + $QtyRefunded);
                            if($qtyToRefund > $itemLeftToShip){
                                $qty = $qtyToRefund - $itemLeftToShip;
                                $amountCaptured = $amountCaptured + $this->calculateItemPrice($orderItem,$qty);
                            }
                            else{
                                $amountNotCaptured = $amountNotCaptured + $this->calculateItemPrice($orderItem,$qtyToRefund);
                            }
                        }
                    }
                }

                if($order->getShipmentsCollection()->count() > 0 && number_format($orderTotal -  $openToCaptureAmount, 2, '.', '') > 0.00){
                    $amountCaptured = $amountCaptured + ($orderShippingAmount - ($orderShippingAmount - $shippingApplied));
                }

                if($capturedDiscount > 0 && $appliedDiscount > 0){
                    if($amount > 0){
                        $amountCaptured = $amountCaptured - $capturedDiscount;
                    }
                }

                if($order->getShipmentsCollection()->count() == 0){
                    $amountNotCaptured = $amountCaptured + $orderShippingAmount;
                }

                if(number_format($amount - $orderTotal, 2, '.', '') == 0.00){
                    //Full Order Refund
                    if($actualOpenToCaptureAmount != $orderTotal){
                        $amount = $amount - $actualOpenToCaptureAmount;
                        $afterpayRefund = true;
                    }
                    $afterpayVoid = true;
                }
                else
                {
                    if($amount > 0){
                        if($amountCaptured > 1){
                            if($amountCaptured > $refundAmountAvailable){
                                $amountToRefund = $amount - $refundAmountAvailable;
                                $amount = $refundAmountAvailable;
                            }
                            else{
                                $amountToRefund = $amount - $amountCaptured;
                                $amount = $amountCaptured;
                            }

                            $afterpayRefund = true;
                        }
                        else{
                            $amountToRefund = $amount;
                            $amount = 0.00;
                        }
                    }

                    if($appliedDiscount > 0){

                        if($amount == 0.00 && $amountToRefund == 0.00){
                            $amountToCapture = min($appliedDiscount - ($rolloverDiscount + $rolloverRefund), $amountNotCaptured);
                        }
                        else{
                            $amountToCapture = min($appliedDiscount - ($rolloverDiscount + $rolloverRefund), $amountToRefund);
                        }

                        if($amountToCapture < 0){
                            $amountToCapture = 0.00;
                        }
                        $reducedRolloverDiscount  = max((($appliedDiscount - $amountCaptured) - $amountToCapture),0.00);

                        if($rolloverDiscount > 0){
                            $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT, max(($rolloverDiscount - $reducedRolloverDiscount),"0.00"));
                        }
                        else{
                            $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT, "0.00");
                        }
                    }

                    if(number_format($amountToRefund - $actualOpenToCaptureAmount, 2, '.', '') == 0.00){
                        $amountToCapture = 0.00;
                        $afterpayVoid    = true;
                    }
                    elseif($amountToRefund < $actualOpenToCaptureAmount && $amountToRefund != 0.00){
                        if($order->getShipmentsCollection()->count()==0){
                            $amountInclShipping   = $amountToRefund + (($orderShippingAmount-$shippingRefunded) - $shippingApplied);

                            if(number_format(($amountInclShipping+ $amountToCapture) -  $orderTotal, 2, '.', '') == 0.00 || number_format(($amountInclShipping+ $amountToCapture) -  $actualOpenToCaptureAmount, 2, '.', '') == 0.00){
                                if($shippingApplied < $orderShippingAmount){
                                    $amountToCapture = $amountToCapture + (($orderShippingAmount-$shippingRefunded) - $shippingApplied);
                                }
                                $afterpayVoid = true;
                            }
                            elseif(number_format($amountInclShipping -  $orderTotal, 2, '.', '') == 0.00 || number_format($amountInclShipping -  $actualOpenToCaptureAmount, 2, '.', '') == 0.00){
                                $afterpayVoid = true;
                            }
                            else{
                                $rolloverRefund = $rolloverRefund + $amountToRefund;
                                $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND, number_format($rolloverRefund, 2, '.', ''));
                                $result['success'] = true;
                            }
                        }
                        else{
                            if($amountToCapture > 0){
                                if(number_format(($amountToRefund + ($amountToCapture - $shippingApplied)) - $actualOpenToCaptureAmount, 2, '.', '') == 0.00 || number_format(($amountToRefund + ($amountToCapture - $shippingApplied)) - $orderTotal, 2, '.', '') == 0.00){
                                    $amountToCapture = $amountToCapture - $shippingApplied;
                                    $afterpayVoid = true;
                                }
                                elseif(number_format(($amountToRefund + ($amountToCapture - $shippingApplied)) - $actualOpenToCaptureAmount, 2, '.', '') > 0.00){
                                    $amountToCapture = $amountToCapture - (($amountToRefund + $amountToCapture) - $openToCaptureAmount);
                                    $afterpayVoid = true;
                                }
                                else{
                                    $rolloverRefund = $rolloverRefund + $amountToRefund;
                                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND, number_format($rolloverRefund, 2, '.', ''));
                                    $result['success'] = true;
                                }
                            }
                            else{
                                $rolloverRefund = $rolloverRefund + $amountToRefund;
                                $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND, number_format($rolloverRefund, 2, '.', ''));
                                $result['success'] = true;
                            }
                        }
                    }
                    elseif($amountToRefund > $actualOpenToCaptureAmount){
                        $amount = $amount + ($amountToRefund - $actualOpenToCaptureAmount);
                        $afterpayRefund = true;
                        $afterpayVoid = true;
                    }
                    else{
                        $result['success'] = true;
                    }
                }
            }
            $result['success'] = $this->afterpayProcessRefund($payment,$order,$amountToCapture,$afterpayRefund,$amount,$afterpayVoid,$orderId,$override);

            if($storeCredit > 0){
                $storeCredit = $storeCredit + $order->getBaseCustomerBalanceRefunded();
                $order->setBaseCustomerBalanceRefunded($storeCredit);
                $order->setCustomerBalanceRefunded($storeCredit);
            }

        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(__('There are no Afterpay payment linked to this order. Please use "refund offline" for this order.'));
        }
        return $result;
    }
    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return array
     */
    public function lastShipmentProcessRefund($payment, $amount)
    {
        $afterpayRefund   = false;
        $afterpayVoid     = false;
        $override         = [];
        $result           = [];
        $amountToCapture  = 0.00;
        $orderId          = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_ORDERID);

        if($orderId) {
            $order = $payment->getOrder();
            if($amount > 0){
                $override = ["website_id" => $order->getStore()->getWebsiteId()];

                $afterpayPaymentStatus = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS);
                if($afterpayPaymentStatus == self::PAYMENT_STATUS_CAPTURED){
                    $afterpayRefund = true;
                }
                elseif($afterpayPaymentStatus == self::PAYMENT_STATUS_PARTIALLY_CAPTURED || $afterpayPaymentStatus == self::PAYMENT_STATUS_AUTH_APPROVED){

                    $orderTotal               = $order->getGrandTotal();
                    $openToCaptureAmount      = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT);
                    $rolloverRefund           = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND);
                    $refundAmountAvailable    = $orderTotal - $openToCaptureAmount;

                    if(number_format($amount - $orderTotal, 2, '.', '') == 0.00){
                        if($openToCaptureAmount != $orderTotal){
                            $amount = $amount - $openToCaptureAmount;
                            $afterpayRefund = true;
                        }
                        $afterpayVoid = true;
                    }
                    else
                    {
                        if(number_format($amount - $openToCaptureAmount, 2, '.', '') == 0.00){
                            $afterpayVoid = true;
                        }
                        elseif($amount < $openToCaptureAmount){

                            $rolloverRefund = $rolloverRefund + $amount;
                            $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND, number_format($rolloverRefund, 2, '.', ''));
                            $result['success'] = true;
                        }
                        elseif($amount > $openToCaptureAmount){
                            $amount = $amount - $openToCaptureAmount;
                            $afterpayRefund = true;
                            $afterpayVoid = true;
                        }
                    }
                }
            }

            $result['success'] = $this->afterpayProcessRefund($payment,$order,$amountToCapture,$afterpayRefund,$amount,$afterpayVoid,$orderId,$override);
        }

        return $result;
    }
    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return array
     */
    public function afterpayProcessRefund($payment,$order,$amountToCapture,$afterpayRefund,$amount,$afterpayVoid,$orderId,$override)
    {
        $success = false;
        //Capture request
        if($amountToCapture > 0){
            $merchant_order_id = $order->getIncrementId();
            $totalAmount= [
                'amount'   => number_format($amountToCapture, 2, '.', ''),
                'currency' => $order->getOrderCurrencyCode()
            ];

            $captureResponse = $this->paymentCapture->send($totalAmount,$merchant_order_id,$orderId,$override);
            $captureResponse = $this->jsonHelper->jsonDecode($captureResponse->getBody());

            if(!array_key_exists("errorCode",$captureResponse)) {
                $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS,$captureResponse['paymentState']);
                if(array_key_exists('openToCaptureAmount',$captureResponse) && !empty($captureResponse['openToCaptureAmount'])){
                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT,number_format($captureResponse['openToCaptureAmount']['amount'], 2, '.', ''));
                }
                $success = true;
            }
            else{
                $this->helper->debug("Transaction Exception : " . json_encode($captureResponse));
                throw new \Magento\Framework\Exception\LocalizedException(__('Afterpay API Error: ' .$captureResponse['message']));
            }
        }
        //Refund reqest
        if($afterpayRefund && $amount > 0){

            $refundResponse = $this->afterpayApiPayment->refund(number_format($amount, 2, '.', ''),$orderId,$order->getOrderCurrencyCode(),$override);

            $refundResponse = $this->jsonHelper->jsonDecode($refundResponse->getBody());

            if (!empty($refundResponse['refundId'])) {
                $success = true;

            } else {
                $this->helper->debug('Afterpay API Error: ' . $refundResponse['message']);
                throw new \Magento\Framework\Exception\LocalizedException(__('Afterpay API Error: ' .$refundResponse['message']));
            }
        }

        if($afterpayVoid){
            //Void request
            $voidResponse = $this->afterpayApiPayment->voidOrder($orderId,$override);
            $voidResponse = $this->jsonHelper->jsonDecode($voidResponse->getBody());

            if(!array_key_exists("errorCode",$voidResponse)) {
                $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::PAYMENT_STATUS, $voidResponse['paymentState']);

                if(array_key_exists('openToCaptureAmount',$voidResponse) && !empty($voidResponse['openToCaptureAmount'])){
                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::OPEN_TOCAPTURE_AMOUNT,$voidResponse['openToCaptureAmount']['amount']);
                }

                if($payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND) > 0){
                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_REFUND, "0.00");
                }
                if($payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT) > 0){
                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_DISCOUNT, "0.00");
                }
                if($payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT) > 0){
                    $payment->setAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ROLLOVER_AMOUNT, "0.00");
                }
                $success = true;
            }
            else{
                $this->helper->debug("Transaction Exception : " . json_encode($voidResponse));
                throw new \Magento\Framework\Exception\LocalizedException(__('Afterpay API Error: ' .$voidResponse['message']));
            }
        }
        return $success;
    }
    /*
      Calculate Total Price for the given item
    */
    public function calculateItemPrice($item,$qty){
        $totalQtyOrdered = $item->getQtyOrdered();
        $totalTaxAmount  = $item->getBaseTaxAmount();
        $totalDiscount   = $item->getDiscountAmount();

        $taxPerItem      = $totalTaxAmount/$totalQtyOrdered;
        $discountPerItem = $totalDiscount / $totalQtyOrdered;

        $pricePerItem    = $item->getPrice() + $taxPerItem;
        $itemPrice       = $qty * ($pricePerItem - $discountPerItem);
        return $itemPrice;
    }
}
