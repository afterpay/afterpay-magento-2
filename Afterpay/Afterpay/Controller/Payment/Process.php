<?php
/**
 * Magento 2 extensions for Mony Payment
 *
 * @author Mony <steven.gunarso@touchcorp.com>
 * @copyright 2016 Mony https://www.monypayments.com.au/
 */
namespace Afterpay\Afterpay\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Sales\Model\OrderFactory as OrderFactory;

/**
 * Class Response
 * @package Mony\Mony\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $orderFactory;
 
    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;

        parent::__construct($context);
    }


    public function execute() {
        $orderId = $this->checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);

        $payment = $order->getPayment();
        $token = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);

        die( json_encode( array("success" => true, "token" => $token) ) );
    }   
}