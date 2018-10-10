<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
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
        \Magento\Quote\Model\ResourceModel\Quote $quoteRepository
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
}
