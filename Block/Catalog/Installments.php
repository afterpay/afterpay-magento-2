<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block\Catalog;

use Afterpay\Afterpay\Block\JsConfig;
use Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;
use Afterpay\Afterpay\Model\Payovertime as AfterpayPayovertime;
use Magento\Framework\Locale\Resolver as Resolver;
use Magento\Framework\Registry as Registry;
use Magento\Framework\View\Element\Template\Context;

class Installments extends JsConfig
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var AfterpayConfig
     */
    private $afterpayConfig;

    /**
     * @var AfterpayPayovertime
     */
    private $afterpayPayovertime;

    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AfterpayConfig $afterpayConfig
     * @param AfterpayPayovertime $afterpayPayovertime
     * @param array $data
     * @param Resolver $localeResolver
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AfterpayConfig $afterpayConfig,
        AfterpayPayovertime $afterpayPayovertime,
        array $data,
        Resolver $localeResolver
    ) {
        $this->registry = $registry;
        $this->afterpayConfig = $afterpayConfig;
        $this->afterpayPayovertime = $afterpayPayovertime;
        $this->localeResolver = $localeResolver;
        parent::__construct($afterpayConfig, $context, $localeResolver, $data);
    }

    /**
     * @return bool
     */
    public function canShow(): bool
    {
        // check if payment is active
        if ($this->_getPaymentIsActive() &&
            $this->afterpayConfig->getCurrencyCode() &&
            $this->afterpayPayovertime->canUseForCurrency($this->afterpayConfig->getCurrencyCode())
        ) {
            $excluded_categories = $this->afterpayConfig->getExcludedCategories();
            if ($excluded_categories != "") {
                $excluded_categories_array = explode(",", $excluded_categories);
                $product = $this->registry->registry('product');
                $categoryids = $product->getCategoryIds();
                foreach ($categoryids as $k) {
                    if (in_array($k, $excluded_categories_array)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTypeOfProduct()
    {
        $product = $this->registry->registry('product');
        return $product->getTypeId();
    }

    /**
     * @return string
     */
    public function getFinalAmount()
    {
        // get product
        $product = $this->registry->registry('product');

        // set if final price is exist
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();

        return !empty($price) ? number_format($price, 2, ".", "") : "0.00";
    }

    /**
     * @return boolean
     */
    public function canUseCurrency()
    {
        $canUse=false;
        //Check for Supported currency
        if ($this->afterpayConfig->getCurrencyCode()) {
            $canUse= $this->afterpayPayovertime->canUseForCurrency($this->afterpayConfig->getCurrencyCode());
        }

        return $canUse;
    }
}
