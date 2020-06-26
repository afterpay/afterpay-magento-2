<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Config;

use Afterpay\Afterpay\Model\Adapter\ApiMode;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\State as State;

/**
 * Class Payovertime
 * @package Afterpay\Afterpay\Model\Config
 */
class Payovertime
{
    /**
     * constant data for static
     */
    const ACTIVE                 = 'active';
    const API_MODE_XML_NODE       = 'api_mode';
    const API_URL_XML_NODE       = 'api_url';
    const WEB_URL_XML_NODE       = 'web_url';
    const MERCHANT_ID_XML_NODE   = 'merchant_id';
    const MERCHANT_KEY_XML_NODE  = 'merchant_key';
    const PAYMENT_ACTION         = 'payment_action';
    const DEBUG_MODE             = 'debug';
    const MIN_TOTAL_LIMIT        = 'min_order_total';
    const MAX_TOTAL_LIMIT        = 'max_order_total';
    const HTTP_HEADER_SUPPORT    = 'http_header_support';
    const EXCLUDE_CATEGORY       = 'exclude_category';

    /**
     * @var ApiMode
     */
    protected $apiMode;
    protected $afterpayPayovertime;

    protected $scopeConfig;
    protected $storeManager;
    protected $request;
    protected $state;

    // protected $storeId = null;

    /**
     * Payovertime constructor.
     * @param ApiMode $apiMode
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ApiMode $apiMode,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Request $request,
        State $state
    ) {
        $this->apiMode = $apiMode;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->state = $state;
    }

    /**
     * Get API Url based on configuration
     *
     * @param string $path
     * @param array $query
     * @param array $override
     * @return bool|string
     */
    public function getApiUrl($path = '', $query = [], $override = [])
    {
        return $this->_getRequestedUrl(self::API_URL_XML_NODE, $path, $query, $override);
    }

    /**
     * Get Web API url based on configuration
     *
     * @param string $path
     * @param array $query
     * @param array $override
     * @return bool|string
     */
    public function getWebUrl($path = '', $query = [], $override = [])
    {
        return $this->_getRequestedUrl(self::WEB_URL_XML_NODE, $path, $query, $override);
    }

    /**
     * Calculated the url to generate api/web url
     *
     * @param $type
     * @param $path
     * @param $query
     * @return bool|string
     */
    protected function _getRequestedUrl($type, $path, $query, $override = [])
    {
        if (!empty($override["website_id"])) {
            $websiteId = $override["website_id"];
            $currentApi = $this->apiMode->getCurrentMode($override);
        } elseif ($this->getWebsiteId() > 1) {
            $websiteId = $this->getWebsiteId();
            $currentApi = $this->apiMode->getCurrentMode(["website_id" => $this->getWebsiteId()]);
        } else {
            $websiteId=1;
            $currentApi = $this->apiMode->getCurrentMode();
        }
        if (array_key_exists($type, $currentApi)) {
            //Get Site config.
            $siteURL = $this->getSiteConfig($currentApi['label'], $type, $websiteId);
            $url = $siteURL . $path;

            // calculate the query
            if (!empty($query)) {
                $url = $url . '?' . http_build_query($query, '', '&amp;');
            }
            // return url
            return $url;
        }
        return false;
    }

    /**
     * Calculated the currency code
     *
     * @return $text
     */
    public function getCurrencyCode()
    {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        return $store->getCurrentCurrencyCode();
    }


     /* Calculated the url to generate api/web url
     *
     * @param $apiMode
     * @param $type
     * @param $websiteId
     * @return $url
     */

    public function getSiteConfig($apiMode, $type, $websiteId)
    {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        //In case of multiple websites, find the currency for the selected store based on the website ID.
        if (!empty($websiteId)) {
            $websites = $storeManager->getWebsites();

            foreach ($websites as $website) {
                foreach ($website->getStores() as $store) {
                    if (!empty($websiteId) && $websiteId == $website->getId()) {
                        $store = $storeManager->getStore($store);
                        $currency = $store->getCurrentCurrencyCode();
                    }
                }
            }
        } else {
            $store = $storeManager->getStore();
            $currency = $store->getCurrentCurrencyCode();
        }

        if ($type=='api_url') {
            if ($apiMode == 'Sandbox') {
                if ($currency == 'USD' || $currency == 'CAD') {
                    $url = 'https://api.us-sandbox.afterpay.com/';
                } else {
                    $url = 'https://api-sandbox.afterpay.com/';
                }
            } elseif ($apiMode == 'Production') {
                if ($currency == 'USD'  || $currency == 'CAD') {
                    $url = 'https://api.us.afterpay.com/';
                } else {
                    $url = 'https://api.afterpay.com/';
                }
            }
        }

        if ($type=='web_url') {
            if ($apiMode == 'Sandbox') {
                $url = 'https://portal.sandbox.afterpay.com/';
            } elseif ($apiMode == 'Production') {
                $url = 'https://portal.afterpay.com/';
            }
        }
        return $url;
    }


    public function getStoreObjectFromRequest()
    {
        //get the store source
        $stores = $this->storeManager->getStores();

        if (!empty($_SERVER['HTTP_REFERER'])) {
            foreach ($stores as $key => $store) {
                $referrer = $_SERVER['HTTP_REFERER'];

                if (strpos($referrer, $store->getBaseUrl()) !== false) {
                    return $store;
                }
            }
        } else {
            foreach ($stores as $key => $store) {
                return $store;
            }
        }
    }

    public function getWebsiteId()
    {

        $website_id = null;

        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $website_id = (int) $this->request->getParam('website', 0);
        } elseif ($this->request->isXmlHttpRequest()) {
            $store = $this->getStoreObjectFromRequest();
            if (!empty($store)) {
                $website_id = $store->getWebsiteId();
            }
        } else {
            $website_id = $this->storeManager->getStore()->getWebsiteId();
        }

        return $website_id;
    }

    /**
     * Get config data
     *
     * @param $path
     * @param $override array
     * @return mixed
     */
    protected function _getConfigData($path, $override = [])
    {
        $website_id = $this->getWebsiteId();

        if (!empty($override["website_id"])) {
            $website_id = $override["website_id"];
        }

        if (!empty($website_id) && $website_id) {
            return $this->scopeConfig->getValue('payment/' . \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '/' . $path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $website_id);
        } else {
            // var_dump($this->scopeConfig->getValue('payment/' . \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '/' . $path, 'default'));
            return $this->scopeConfig->getValue('payment/' . \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '/' . $path, 'default');
        }
    }

    /**
     * Get config API mode
     *
     * @return string (development | qa | sandbox | production)
     */
    public function getApiMode()
    {
        return $this->_getConfigData(self::API_MODE_XML_NODE);
    }

    /**
     * Get HTTP Header Support Fallback
     *
     * @return bool
    */
    public function isHTTPHeaderSupportEnabled()
    {
        return $this->_getConfigData(self::HTTP_HEADER_SUPPORT);
    }

    /**
     * Get config for merchant id
     *
     * @return mixed
     */
    public function getMerchantId($override = [])
    {
        return $this->_cleanup_string($this->_getConfigData(self::MERCHANT_ID_XML_NODE, $override));
    }

    /**
     * Get config for merchant key
     *
     * @return mixed
     */
    public function getMerchantKey($override = [])
    {
        return $this->_cleanup_string($this->_getConfigData(self::MERCHANT_KEY_XML_NODE, $override));
    }

    /**
     * @return mixed
     */
    public function getPaymentAction()
    {
        return $this->_getConfigData(self::PAYMENT_ACTION);
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)(int)$this->_getConfigData(self::DEBUG_MODE);
    }

    /**
     * @return bool
     */
    public function isActive($override = [])
    {
        return (bool)(int)$this->_getConfigData(self::ACTIVE,$override);
    }

    /**
     * @return int
     */
    public function getMaxOrderLimit()
    {
        return (int)$this->_getConfigData(self::MAX_TOTAL_LIMIT);
    }

    /**
     * @return int
     */
    public function getMinOrderLimit()
    {
        return (int)$this->_getConfigData(self::MIN_TOTAL_LIMIT);
    }

	/**
     * @return int
     */
    public function getExcludedCategories()
    {
        return $this->_getConfigData(self::EXCLUDE_CATEGORY);
    }

    /**
     * Filters the String for screcret keys
     *
     * @return string Authorization code
     * @since 1.0.1
     */
    private function _cleanup_string($string)
    {
        $result = preg_replace("/[^a-zA-Z0-9]+/", "", $string);
        return $result;
    }
}
