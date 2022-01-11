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
 * Class AfterpayOrderTokenCheck
 * @package Afterpay\Afterpay\Model\Adapter\V2
 */
class AfterpayOrderTokenCheck
{
    protected $afterpayApiCall;
    protected $afterpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;
    protected $isTokenChecked = false;

    /**
     * AfterpayOrderTokenCheck constructor.
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
     * @param $token
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate($token = null)
    {
        if(empty($token)){
            $this->helper->debug("Token Exception: Token is missing." );
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue when processing the payment. Invalid Token'));
        }else {
            try {
                $response = $this->afterpayApiCall->send(
                    $this->afterpayConfig->getApiUrl('v2/checkouts/' . $token),
                    [],
                    \Magento\Framework\HTTP\ZendClient::GET
                );
            } catch (\Exception $e) {
                $this->helper->debug("Token Exception: " . $e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue when processing the payment. Invalid Token'));
            }
        }
        return $response;
    }

    public function setIsTokenChecked(bool $isTokenChecked)
    {
        $this->isTokenChecked = $isTokenChecked;
        return $this;
    }


    public function getIsTokenChecked()
    {
        return $this->isTokenChecked;
    }

}
