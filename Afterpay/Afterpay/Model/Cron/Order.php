<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model\Cron;

/**
 * Class Cron
 * @package Afterpay\Afterpay\Model
 */
class Order
{
    /**
     * Constant variable
     */
    const ORDERS_PROCESSING_LIMIT = 50;

    /**
     * @var |OrderFactory|Response|AfterpayPayment|JsonData|
     */
    protected $orderFactory;

    protected $afterpayResponse;
    protected $afterpayPayment;
    protected $helper;

    protected $jsonHelper;

    protected $date;
    protected $timezone;

    /**
     * Order constructor.
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Afterpay\Afterpay\Model\Response $afterpayResponse
     * @param \Afterpay\Afterpay\Model\Adapter\AfterpayPayment $afterpayPayment
     * @param \Afterpay\Afterpay\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Afterpay\Afterpay\Model\Response $afterpayResponse,
        \Afterpay\Afterpay\Model\Adapter\AfterpayPayment $afterpayPayment,
        \Afterpay\Afterpay\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->orderFactory = $orderFactory;

        $this->afterpayResponse = $afterpayResponse;
        $this->afterpayPayment = $afterpayPayment;
        $this->helper = $helper;

        $this->jsonHelper = $jsonHelper;
        $this->date = $date;
        $this->timezone = $timezone;
    }

    /**
     * crontab function to get payment update
     */
    public function execute()
    {
        // adding current magento scope datetime with 30 mins calculations
        $requestDate = $this->date->gmtDate(null, $this->timezone->scopeTimeStamp() - (\Afterpay\Afterpay\Model\Payovertime::MINUTE_DELAYED_ORDER * 60));

        /**
         * Load the order along with payment method and additional info
         */
        $orderCollection = $this->orderFactory->create()->getCollection();

        // join with payment table
        $orderCollection->getSelect()
            ->join(
                array('payment' => 'sales_order_payment'),
                'main_table.entity_id = payment.parent_id',
                array('method', 'additional_information', 'last_trans_id')
            );

        // add filter for state and payment method
        $orderCollection->addFieldToFilter('main_table.state', array('eq' => \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW))
            ->addFieldToFilter('payment.method', array('eq' => \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE))
            ->addFieldToFilter('main_table.created_at', array('lt' => $requestDate));

        $orderCollection->setPageSize(50); //load the first 50 order first

        // set start cron
        $this->helper->debug('Cron Process Running');

        /**
         * Looping the order and processing each one
         */
        foreach ($orderCollection as $order) {

            //double check the order cooldown timing
            $orderScopeDateArray = get_object_vars($this->timezone->date($order->getCreatedAt()));
            $orderScopeDate = $this->date->gmtDate(null, $orderScopeDateArray['date']);

            if ($orderScopeDate >= $requestDate) {
                continue;
            }

            // load payment
            $payment = $order->getPayment();

            // check if token is exist
            if ($token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN)) {
                $response = $this->afterpayPayment->getPaymentByToken($token);
                $response = $this->jsonHelper->jsonDecode($response->getBody());

                // check if order exist usig that token
                if (isset($response['totalResults']) && $response['totalResults'] > 0) {
                    $result = $response['results'][0];
                    // check the result of API
                    switch ($result['status']) {
                        case \Afterpay\Afterpay\Model\Status::STATUS_APPROVED:
                            // Adding order ID to payment, create invoice and processing the order
                            $this->afterpayResponse->updatePayment($payment->getOrder(), $result['id']);
                            $this->afterpayResponse->createInvoiceAndUpdateOrder($payment->getOrder(), $result['id']);
                            break;
                        case \Afterpay\Afterpay\Model\Status::STATUS_DECLINED;
                            // cancel the order if found order declined
                            $this->afterpayResponse->cancelOrder($payment->getOrder(), __('Payment declined by Afterpay'));
                            break;
                        case \Afterpay\Afterpay\Model\Status::STATUS_FAILED;
                            // cancel the order if found order declined
                            $this->afterpayResponse->cancelOrder($payment->getOrder(), __('Payment Failed'));
                            break;
                    }
                } else {
                    // if order is just an abandoned order
                    $order->addStatusHistoryComment(__('Customer abandoned the payment process'));
                    $this->afterpayResponse->cancelOrder($payment->getOrder());
                }
            } else {
                // cancel order if token is not found
                $this->afterpayResponse->cancelOrder($payment->getOrder());
            }
        }

        $this->helper->debug('Cron Process Finished');
    }
}