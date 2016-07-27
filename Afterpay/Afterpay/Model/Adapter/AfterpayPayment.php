<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model\Adapter;

use \Afterpay\Afterpay\Model\Adapter\Afterpay\Call;
use \Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

class AfterpayPayment
{
    /**
     * constant variable
     */
    const API_RESPONSE_APPROVED = 'APPROVED';

    /**
     * @var Call
     */
    protected $afterpayApiCall;
    protected $afterpayConfig;
    protected $objectManagerInterface;
    protected $jsonHelper;

    /**
     * AfterpayPayment constructor.
     * @param Call $afterpayApiCall
     * @param AfterpayConfig $afterpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Call $afterpayApiCall,
        AfterpayConfig $afterpayConfig,
        ObjectManagerInterface $objectManagerInterface,
        JsonHelper $jsonHelper
    ) {
        $this->afterpayApiCall = $afterpayApiCall;
        $this->afterpayConfig = $afterpayConfig;
        $this->objectManagerInterface = $objectManagerInterface;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @param $afterpayOrderId
     * @return mixed|\Zend_Http_Response
     */
    public function getPayment($afterpayOrderId)
    {
        return $this->_getPayment($afterpayOrderId);
    }

    /**
     * @param $token
     * @return mixed|\Zend_Http_Response
     */
    public function getPaymentByToken($token)
    {
        return $this->_getPayment($token, true);
    }

    /**
     * @param $input
     * @param bool $useToken
     * @return mixed|\Zend_Http_Response
     */
    protected function _getPayment($input, $useToken = false)
    {
        // set url for ID
        $url = $this->afterpayConfig->getApiUrl('merchants/orders/' . $input);

        // if request using token create url for it
        if ($useToken) {
            $url = $this->afterpayConfig->getApiUrl('merchants/orders/', array('token' => $input));
        }

        try {
            $response = $this->afterpayApiCall->send($url);
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode(array(
                'error' => 1,
                'message' => $e->getMessage()
            )));
        }

        return $response;
    }

    /**
     * @param $amount
     * @param $orderId
     * @param string $currency
     * @return mixed|\Zend_Http_Response
     */
    public function refund($amount, $orderId, $currency = 'AUD')
    {
        // create url to request refunds
        $url = $this->afterpayConfig->getApiUrl('merchants/orders/' . $orderId . '/refunds');

        // generate body to be sent to refunds
        $body = array(
            'amount'    => array(
                'amount'    => abs($amount) * -1, // Afterpay API requires a negative amount
                'currency'  => $currency,
            ),
            'merchantRefundId'  => null
        );

        // refunding now
        try {
            $response = $this->afterpayApiCall->send(
                $url,
                $body,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode(array(
                'error' => 1,
                'message' => $e->getMessage()
            )));
        }

        return $response;
    }
}