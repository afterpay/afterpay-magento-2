<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Block\Catalog;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product as Product;
use Magento\Framework\Registry as Registry;
use Magento\Directory\Model\Currency as Currency;
use Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;

class Installments extends Template
{
    /**
     * @var Product
     */
    protected $product;
    protected $registry;
    protected $currency;
    protected $afterpayConfig;

    /**
     * Installments constructor.
     * @param Template\Context $context
     * @param Product $product
     * @param Registry $registry
     * @param Currency $currency
     * @param AfterpayConfig $afterpayConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Product $product,
        Registry $registry,
        Currency $currency,
        AfterpayConfig $afterpayConfig,
        array $data
    ) {
        $this->product = $product;
        $this->registry = $registry;
        $this->currency = $currency;
        $this->afterpayConfig = $afterpayConfig;
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
     * @return string
     */
    public function getInstallmentsAmount()
    {
        // get product
        $product = $this->registry->registry('product');

        // set if final price is exist
        if ($price = $product->getFinalPrice()) {
            return $this->currency->getCurrencySymbol() . $price / 4;
        }
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
        
        // get current product
        $product = $this->registry->registry('product');

        // check if price is above max or min limit
        if ($product->getFinalPrice() > $this->afterpayConfig->getMaxOrderLimit() // greater than max order limit
            || $product->getFinalPrice() < $this->afterpayConfig->getMinOrderLimit()) { // lower than min order limit
            return false;
        }

        return true;
    }
}