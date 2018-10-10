<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter\V1;

use \Afterpay\Afterpay\Model\Adapter\Afterpay\Call;
use \Afterpay\Afterpay\Model\Config\Payovertime as PayovertimeConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Afterpay\Afterpay\Helper\Data as Helper;

/**
 * Class AfterpayOrderTokenCheck
 * @package Afterpay\Afterpay\Model\Adapter\V1
 */
class AfterpayOrderTokenCheck
{
    /**
     * Constant data
     */
    const DECIMAL_PRECISION = 2;
    // const PAYMENT_TYPE_CODE = 'PBI';

    /**
     * @var Call
     */
    protected $afterpayApiCall;
    protected $afterpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;

    /**
     * AfterpayOrderToken constructor.
     * @param Call $afterpayApiCall
     * @param PayovertimeConfig $afterpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param JsonHelper $jsonHelper
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
     * @param $object
     * @param $code
     * @param array $override
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate($token = NULL)
    {
        try {
            $response = $this->afterpayApiCall->send(
                $this->afterpayConfig->getApiUrl('v1/orders/' . $token ),
                array(),
                \Magento\Framework\HTTP\ZendClient::GET
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