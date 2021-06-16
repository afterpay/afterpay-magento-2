<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Afterpay\Afterpay\Model\Config\Payovertime as AfterpayConfig;
use Afterpay\Afterpay\Model\Payovertime as AfterpayPayovertime;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Locale\Resolver as Resolver;
use Magento\Framework\Serialize\Serializer\Json as JsonHelper;


class Button extends \Afterpay\Afterpay\Block\JsConfig
{
    /**
     * @var AfterpayConfig
     */
    protected $afterpayConfig;
    protected $afterpayPayovertime;
    protected $checkoutSession;
    protected $customerSession;
    /**
     * @var JsonHelper
     */
    protected $_jsonHelper;

    /**
     * Button constructor.
     * @param Context $context
     * @param AfterpayConfig $afterpayConfig
     * @param AfterpayPayovertime $afterpayPayovertime
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param JsonHelper $jsonHelper
     * @param array $data
     * @param Resolver $localeResolver
     */
    public function __construct(
        Context $context,
        AfterpayConfig $afterpayConfig,
        AfterpayPayovertime $afterpayPayovertime,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        JsonHelper $jsonHelper,
        array $data=[],
        Resolver $localeResolver
    ) {
        $this->afterpayConfig = $afterpayConfig;
        $this->afterpayPayovertime = $afterpayPayovertime;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        parent::__construct($afterpayConfig,$afterpayPayovertime,$context, $localeResolver,$jsonHelper,$data);

    }

    /**
     * @return bool
     */
    protected function _getPaymentIsActive()
    {
        return $this->afterpayConfig->isActive();
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
		else{
			//Check for Supported currency
			if($this->afterpayConfig->getCurrencyCode()){

				$quote = $this->checkoutSession->getQuote();
				// get grand total (final amount need to be paid)
				$grandTotal =$quote->getGrandTotal();
				$excluded_categories=$this->afterpayConfig->getExcludedCategories();

				if($this->afterpayPayovertime->canUseForCurrency($this->afterpayConfig->getCurrencyCode()) ){

					if($excluded_categories !=""){
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						$productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
						$excluded_categories_array =  explode(",",$excluded_categories);

						foreach ($quote->getAllVisibleItems() as $item) {
							$productid = $item->getProductId();
							$product=$productRepository->getById($productid);
							$categoryids = $product->getCategoryIds();

							foreach($categoryids as $k)
							{
								if(in_array($k,$excluded_categories_array)){
									return false;
								}
							}
						}
					}
					return true;
				}
				else{
					return false;
				}
			}
			else {
				return false;
			}
		}
    }

    /**
     * @return string
     */
    public function getFinalAmount()
    {

        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();

        return !empty($grandTotal)?number_format($grandTotal, 2,".",""):"0.00";

    }
    /*
     * @return boolean
    */
    public function canUseCurrency()
    {
        $canUse=false;
        //Check for Supported currency
        if($this->afterpayConfig->getCurrencyCode())
        {
            $canUse= $this->afterpayPayovertime->canUseForCurrency($this->afterpayConfig->getCurrencyCode());
        }

        return $canUse;

    }
    /*
    * @return boolean
    */
    public function isWithinLimits()
    {
        $isWithinLimits=false;
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        if($grandTotal > 0 && $this->afterpayConfig->getMaxOrderLimit() >= $grandTotal && $this->afterpayConfig->getMinOrderLimit() <= $grandTotal){
            $isWithinLimits = true;
        }
        return $isWithinLimits;
    }
    /*
    * @return boolean
    **/
    public function isQuoteVirtual()
    {
        return $this->checkoutSession->getQuote()->isVirtual();
    }
}
