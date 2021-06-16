<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block;

use Afterpay\Afterpay\Model\Config\Payovertime as ConfigPayovertime;
use Afterpay\Afterpay\Model\Payovertime;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\Serializer\Json as JsonHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class JsConfig extends Template
{
    protected $_configPayovertime;
    private $localeResolver;
    protected $_payOvertime;
    /**
     * @var JsonHelper
     */
    protected $_jsonHelper;


    /**
     * Config constructor.
     *
     * @param ConfigPayovertime $configPayovertime
     * @param Payovertime $payOvertime
     * @param Context $context
     * @param Resolver $localeResolver
     * @param JsonHelper $jsonHelper
     * @param array $data
     */
    public function __construct(
        ConfigPayovertime $configPayovertime,
        Payovertime $payOvertime,
        Context $context,
        Resolver $localeResolver,
        JsonHelper $jsonHelper,
        array $data = []
    ) {

        $this->_configPayovertime = $configPayovertime;
        $this->_payOvertime = $payOvertime;
        $this->localeResolver = $localeResolver;
        $this->_jsonHelper = $jsonHelper;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        return $this;
    }
    /**
     * @return float
     */
    public function getMaxOrderLimit()
    {
        return number_format($this->_configPayovertime->getMaxOrderLimit(), 2,".","");
    }

    /**
     * @return float
     */
    public function getMinOrderLimit()
    {
        return number_format($this->_configPayovertime->getMinOrderLimit(), 2,".","");
    }

    /**
     * @return bool
     */
    protected function _getPaymentIsActive()
    {
        return $this->_configPayovertime->isActive();
    }

    /* Get Current Locale
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        $currentLocale=$this->localeResolver->getLocale();
        $country_code=$this->_configPayovertime->getCurrentCountryCode();
        if(!empty($country_code) && stripos($currentLocale,$country_code)=== false){
            $currentLocale="en_".strtoupper($country_code);
        }

        return $currentLocale; // eg. fr_CA
    }

    /**
     * Get JS Library URL
     *
     * @return string
     */
    public function getAfterpayJsLibUrl()
    {
        return $this->_configPayovertime->getJSLibUrl('afterpay-1.x.js');
    }
    /**
     * check if payment is active
     *
     * @return bool
     */
    public function isPaymentMethodActive()
    {
        $isPaymentMethodActive=true;
        if (!$this->_getPaymentIsActive()) {
            $isPaymentMethodActive= false;
        }
        return  $isPaymentMethodActive;
    }

    /* Get Current Currency
     *
     * @return string
     */
    public function getCurrentCurrency()
    {
        return $this->_configPayovertime->getCurrencyCode(); // eg. AUD
    }

    /* Get Base Currency
    *
    * @return string
    */
    public function getBaseCurrency()
    {
        return $this->_payOvertime->getStoreCurrencyCode(); // eg. AUD
    }

    /**
     * check if payment is active for product page
     *
     * @return bool
     */
    public function isDisplayOnProductPage()
    {
        $isEnabledForProductPage=true;
        if (!$this->_configPayovertime->isEnabledForProductDisplayPage()) {
            $isEnabledForProductPage= false;
        }
        return  $isEnabledForProductPage;
    }

    /**
     * check if payment is active for cart page
     *
     * @return int
     */
    public function isDisplayOnCartPage()
    {
        return $this->_configPayovertime->isEnabledForCartPage();
    }
    /**
     * check if express checkout is active for minicart
     *
     * @return int
     */
    public function isDisplayECOnMiniCart()
    {
        return $this->_configPayovertime->isExpressCheckoutMiniCartPage();
    }

    /**
     * Get Express Checkout JS URL
     *
     * @return bool|string
     */
    public function getAfterpayECJsUrl()
    {
         return $this->_configPayovertime->getWebUrl('afterpay.js',array("merchant_key"=>"magento2"));
    }

    /**
     * Get Afterpay Configs
     *
     * @return string
     */
    public function getAfterpayConfigs()
    {
        /**
         * adding config array
         */
        $config = array(
            'paymentActive'=>$this->_getPaymentIsActive(),
            'currencyCode' => $this->getCurrentCurrency(),
            'baseCurrencyCode' => $this->getBaseCurrency(),
            'minLimit'=>$this->getMinOrderLimit(),
            'maxLimit'=>$this->getMaxOrderLimit()
        );
        return $this->getJsonEncode($config);
    }
    /**
     * check if enable express checkout for product page
     *
     * @return int
     */
    public function isDisplayEConProductPage()
    {
       return  $this->_configPayovertime->isExpressCheckoutProductPage();
    }
    /**
     * check if enable express checkout for cart page
     *
     * @return int
     */
    public function isDisplayEConCartPage()
    {
       return  $this->_configPayovertime->isExpressCheckoutCartPage();
    }
    /**
     * Get store identifier
     *
     * @return  int
     */
    public function getStore()
    {
        return $this->_configPayovertime->getStoreId();
    }

    /**
     * @param $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->_jsonHelper->serialize($data); // it's same as like json_encode
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_jsonHelper->unserialize($data); // it's same as like json_decode
    }
}
