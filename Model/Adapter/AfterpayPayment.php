<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2019 Afterpay https://www.afterpay.com
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
    public function getPayment($afterpayOrderId, $override = [])
    {
        return $this->_getPayment($afterpayOrderId, false, $override);
    }

    /**
     * @param $token
     * @return mixed|\Zend_Http_Response
     */
    public function getPaymentByToken($token, $override = [])
    {
        return $this->_getPayment($token, true, $override);
    }

    /**
     * @param $input
     * @param bool $useToken
     * @return mixed|\Zend_Http_Response
     */
    protected function _getPayment($input, $useToken = false, $override = [])
    {
        // set url for ID
        $url = $this->afterpayConfig->getApiUrl('merchants/orders/' . $input, [], $override);

        // if request using token create url for it
        if ($useToken) {
            $url = $this->afterpayConfig->getApiUrl('merchants/orders/', ['token' => $input], $override);
        }

        try {
            $response = $this->afterpayApiCall->send($url, null, null, $override);
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode([
                'error' => 1,
                'message' => $e->getMessage()
            ]));
        }

        return $response;
    }

    /**
     * @param $amount
     * @param $orderId
     * @param string $currency
     * @param array $override
     * @return mixed|\Zend_Http_Response
     */
    public function refund($amount, $orderId, $currency = 'AUD', $override = [])
    {
        // create url to request refunds
        $url = $this->afterpayConfig->getApiUrl('v2/payments/' . $orderId . '/refund', [], $override);

        // generate body to be sent to refunds
        $body = [
		    'requestId'  => uniqid(),
            'amount'    => [
                'amount'    => abs(round($amount, 2)), // Afterpay API V2 requires a positive amount
                'currency'  => $currency,
            ],
            'merchantReference'  => $orderId
        ];


        // refunding now
        try {
            $response = $this->afterpayApiCall->send(
                $url,
                $body,
                \Magento\Framework\HTTP\ZendClient::POST,
                $override
            );
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode([
                'error' => 1,
                'message' => $e->getMessage()
            ]));
        }

        return $response;
    }
	
	/**
     * @param $orderId
     * @param array $override
     * @return mixed|\Zend_Http_Response
     */
    public function voidOrder($orderId, $override = [])
    {
        // create url to request refunds
        $url = $this->afterpayConfig->getApiUrl('v2/payments/' . $orderId . '/void', [], $override);

        // generate body to be sent to refunds
        $body = [
            'orderId'  => $orderId
        ];


        // refunding now
        try {
            $response = $this->afterpayApiCall->send(
                $url,
                $body,
                \Magento\Framework\HTTP\ZendClient::POST,
                $override
            );
        } catch (\Exception $e) {
            $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode([
                'error' => 1,
                'message' => $e->getMessage()
            ]));
        }

        return $response;
    }
}
