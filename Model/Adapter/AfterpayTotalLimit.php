<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter;

use \Afterpay\Afterpay\Model\Adapter\Afterpay\Call;
use \Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Class AfterpayTotalLimit
 * @package Afterpay\Afterpay\Model\Adapter
 */
class AfterpayTotalLimit
{
    /**
     * @var Call
     */
    protected $afterpayApiCall;
    protected $afterpayConfig;
    protected $objectManagerInterface;
    protected $jsonHelper;

    /**
     * AfterpayTotalLimit constructor.
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
     * @return mixed|\Zend_Http_Response
     */
    public function getLimit($override = [])
    {
        $queryString=array("include"=>"cbt");
        /** @var \Afterpay\Afterpay\Model\Config\Payovertime $url */
        $url = $this->afterpayConfig->getApiUrl('v2/configuration',$queryString); //V2

        // calling API
        try {
            $response = $this->afterpayApiCall->send($url, null, null, $override);
        } 
        catch (\Exception $e) {

            $state =  $this->objectManagerInterface->get('Magento\Framework\App\State');
            if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                throw new \Exception($e->getMessage());
            }
            else {
                $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
                $response->setBody($this->jsonHelper->jsonEncode([
                    'error' => 1,
                    'message' => $e->getMessage()
                ]));
            }
        }

        return $response;
    }
}
