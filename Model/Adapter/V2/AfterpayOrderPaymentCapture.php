<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter\V2;

use \Afterpay\Afterpay\Model\Adapter\Afterpay\Call;
use \Afterpay\Afterpay\Model\Config\Payovertime as PayovertimeConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Afterpay\Afterpay\Helper\Data as Helper;

/**
 * Class AfterpayOrderPaymentCapture
 * @package Afterpay\Afterpay\Model\Adapter\V2
 */
class AfterpayOrderPaymentCapture
{
    protected $afterpayApiCall;
    protected $afterpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;

    /**
     * AfterpayOrderPaymentCapture constructor.
     * @param Call $afterpayApiCall
     * @param PayovertimeConfig $afterpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param StoreManagerInterface $storeManagerInterface
     * @param JsonHelper $jsonHelper
     * @param Helper $afterpayHelper
     */
    public function __construct(
        Call $afterpayApiCall,
        PayovertimeConfig $afterpayConfig,
        ObjectManagerInterface $objectManagerInterface,
        StoreManagerInterface $storeManagerInterface,
        JsonHelper $jsonHelper,
        Helper $afterpayHelper
    ) {
        $this->afterpayApiCall = $afterpayApiCall;
        $this->afterpayConfig = $afterpayConfig;
        $this->objectManagerInterface = $objectManagerInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->jsonHelper = $jsonHelper;
        $this->helper = $afterpayHelper;
    }

    /**
     * @param $totalAmount
     * @param $merchant_order_id
     * @param array $afterpay_order_id
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function send($totalAmount,$merchant_order_id,$afterpay_order_id,$override=[])
    {
        $requestData = $this->_buildPaymentCaptureRequest($totalAmount, $merchant_order_id);

        try {
            $response = $this->afterpayApiCall->send(
                $this->afterpayConfig->getApiUrl('v2/payments/'.$afterpay_order_id.'/capture',[],$override),
                $requestData,
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
     * @param $totalAmount
     * @param $merchant_order_id
     * @return array
     */
    protected function _buildPaymentCaptureRequest($totalAmount, $merchant_order_id)
    {
		$params['requestId'] = uniqid();
        $params['merchantReference'] = $merchant_order_id;
        $params['amount'] = $totalAmount;

        return $params;
    }
}
