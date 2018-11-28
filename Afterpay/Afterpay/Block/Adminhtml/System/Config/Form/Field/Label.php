<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use \Afterpay\Afterpay\Helper\Data as AfterpayHelper;

class Label extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $helper;

    /**
     * Call constructor.
     * @param AfterpayHelper $helper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        AfterpayHelper $helper
    ) {
        $this->helper = $helper;
        parent::__construct($context);
    }


    protected function _getElementHtml(AbstractElement $element)
    {
        $version = $this->helper->getModuleVersion();
        return $version;
    }
}
