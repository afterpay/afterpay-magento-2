<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model;

class Token
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     * @var \Magento\Checkout\Model\Session
     */
    protected $jsonHelper;
    protected $checkoutSession;

    /**
     * Token constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $return
     * @return string
     */
    public function saveAndReturnToken($return)
    {
        // checking if afterpay payment is being use
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() == \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE) {
            $data = $payment->getAdditionalInformation(\Afterpay\Afterpay\Model\Payovertime::ADDITIONAL_INFORMATION_KEY_TOKEN);
            $return = $this->jsonHelper->jsonEncode(array(
                'token' => $data
            ));
        }

        return $return;
    }
}