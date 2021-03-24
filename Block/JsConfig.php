<?php

namespace Afterpay\Afterpay\Block;


class JsConfig extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Payovertime $_payOverTime
     */
    protected $_payOverTime;

    /**
     * @var Data $_dataHelper
     */
    protected $_dataHelper;
    private $localeResolver;

    /**
     * Config constructor.
     *
     * @param Payovertime $payovertime
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Afterpay\Afterpay\Model\Config\Payovertime $payovertime,
        \Magento\Framework\View\Element\Template\Context $context,        
        \Magento\Framework\Locale\Resolver $localeResolver,
        array $data = []
    ) {
    
        $this->_payOverTime = $payovertime;
        $this->localeResolver = $localeResolver;

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
        return number_format($this->_payOverTime->getMaxOrderLimit(), 2,".","");
    }
    
    /**
     * @return float
     */
    public function getMinOrderLimit()
    {
        return number_format($this->_payOverTime->getMinOrderLimit(), 2,".","");
    }
    
    /**
     * @return bool
     */
    protected function _getPaymentIsActive()
    {
        return $this->_payOverTime->isActive();
    }
    
    /* Get Current Locale
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        $currentLocale=$this->localeResolver->getLocale();
        $country_code=$this->_payOverTime->getCurrentCountryCode();
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
        return $this->_payOverTime->getJSLibUrl('afterpay-1.x.js');
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
        return $this->_payOverTime->getCurrencyCode(); // eg. AUD
    }
    
    /**
     * check if payment is active for product page
     *
     * @return bool
     */
    public function isDisplayOnProductPage()
    {
        $isEnabledForProductPage=true;
        if (!$this->_payOverTime->isEnabledForProductDisplayPage()) {
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
        return $this->_payOverTime->isEnabledForCartPage();
    }
    
    /**
     * Get Express Checkout JS URL 
     *
     * @return bool|string
     */
    public function getAfterpayECJsUrl()
    {
        $express_checkout_key=$this->_payOverTime->getExpressCheckoutKey();
        return $this->_payOverTime->getWebUrl('afterpay.js',array("merchant_key"=>!empty($express_checkout_key)?$express_checkout_key:""));
    }
}
