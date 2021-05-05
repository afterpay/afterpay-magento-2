<?php

namespace Afterpay\Afterpay\Block;

use Magento\Framework\View\Element\Template;
use Afterpay\Afterpay\Model\Config\Payovertime;
use Afterpay\Afterpay\Model\Payovertime as AfterpayPayovertime;
use Magento\Framework\Json\Helper\Data;

class Config extends Template
{
    /**
     * @var Payovertime $_payOverTime
     */
    protected $_payOverTime;

    /**
     * @var Data $_dataHelper
     */
    protected $_dataHelper;

    /**
     * Config constructor.
     *
     * @param Payovertime $payovertime
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Payovertime $payovertime,
        Data $dataHelper,
        Template\Context $context,
        AfterpayPayovertime $afterpayPayovertime,
        array $data
    ) {
    
        $this->_payOverTime = $payovertime;
        $this->_dataHelper = $dataHelper;
        $this->afterpayPayovertime = $afterpayPayovertime;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        return $this;
    }

    /**
     * Get URL to afterpay.js
     *
     * @return bool|string
     */
    public function getAfterpayJsUrl()
    {
        return $this->_payOverTime->getWebUrl('afterpay.js');
    }
	/**
     * @return bool
     */
	public function checkCurrency()
    {
        return $this->afterpayPayovertime->canUseForCurrency($this->_payOverTime->getCurrencyCode()) && $this->_payOverTime->isActive();
		
    }
}
