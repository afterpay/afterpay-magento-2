<?php

namespace Afterpay\Afterpay\Block;

use Magento\Framework\View\Element\Template;
use Afterpay\Afterpay\Model\Config\Payovertime;
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
        array $data
    ) {
    
        $this->_payOverTime = $payovertime;
        $this->_dataHelper = $dataHelper;

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
}
