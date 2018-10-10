<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter;

class ApiMode
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $environments;

    /**
     * Mode constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $environments
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, $environments = [])
    {
        $this->scopeConfig = $scopeConfig;
        $this->environments = $environments;
    }

    /**
     * Get All API modes from di.xml
     *
     * @return array
     */
    public function getAllApiModes()
    {
        return $this->environments;
    }

    /**
     * Get current API mode based on configuration
     *
     * @return array
     */
    public function getCurrentMode($override = array())
    {
        if( !empty( $override["website_id"] ) ) {
            return $this->environments[$this->scopeConfig->getValue('payment/afterpaypayovertime/api_mode', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $override["website_id"])];
        }
        return $this->environments[$this->scopeConfig->getValue('payment/afterpaypayovertime/api_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)];
    }
}