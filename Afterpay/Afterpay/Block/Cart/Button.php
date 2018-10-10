<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Currency as Currency;
use Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;

class Button extends Template
{
    /**
     * @var AfterpayConfig
     */
    protected $afterpayConfig;
    protected $checkoutSession;
    protected $currency;
    protected $customerSession;
    protected $componentRegistrar;

    /**
     * Button constructor.
     * @param Template\Context $context
     * @param AfterpayConfig $afterpayConfig
     * @param CheckoutSession $checkoutSession
     * @param Currency $currency
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AfterpayConfig $afterpayConfig,
        CheckoutSession $checkoutSession,
        Currency $currency,
        CustomerSession $customerSession,
        ComponentRegistrar $componentRegistrar,
        array $data
    ) {
        $this->afterpayConfig = $afterpayConfig;
        $this->checkoutSession = $checkoutSession;
        $this->currency = $currency;
        $this->customerSession = $customerSession;
        $this->componentRegistrar = $componentRegistrar;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    protected function _getPaymentIsActive()
    {
        return $this->afterpayConfig->isActive();
    }

    /**
     * @return float
     */
    public function getInstallmentsTotal()
    {
        $quote = $this->checkoutSession->getQuote();

        if ($grandTotal = $quote->getGrandTotal()) {
            return $grandTotal / 4;
        }
    }

    /**
     * @return string
     */
    public function getInstallmentsTotalHtml()
    {
        return $this->getCurrency()->getCurrencySymbol() . number_format( $this->getInstallmentsTotal(), 2 );
    }

    /**
     * @return Currency
     */
    protected function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return bool
     */
    public function canShow()
    {
        // check if payment is active
        if (!$this->_getPaymentIsActive()) {
            return false;
        }

        // get grand total (final amount need to be paid)
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();

        // check if total is still in limit range
        if ($this->afterpayConfig->getMaxOrderLimit() < $grandTotal // greater than max order total
            || $this->afterpayConfig->getMinOrderLimit() > $grandTotal) { // lower than min order total
            return false;
        }

        // all ok
        return true;
    }

    /**
     * Calculate region specific Instalment Text for Cart page
     * @return string
     */
    public function getCartPageText()
    {
        $currencyCode = $this->afterpayConfig->getCurrencyCode();
        $assetsPath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Afterpay_Afterpay');

        if(file_exists($assetsPath.'/assets.ini'))
        {
            $assets = parse_ini_file($assetsPath.'/assets.ini',true);
            $assets_cart_page = $assets[$currencyCode]['cart_page'];
            $assets_cart_page = str_replace(array('[modal-href]'), 
                    array('javascript:void(0)'), $assets_cart_page);
        } 
        else
        {
            $assets_cart_page = '';       
        }
        return $assets_cart_page;
       
    }
}