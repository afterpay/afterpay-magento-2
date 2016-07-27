<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $afterpayConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Afterpay\Afterpay\Model\Logger\Logger $logger,
        \Afterpay\Afterpay\Model\Config\Payovertime $afterpayConfig
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->afterpayConfig = $afterpayConfig;

    }

    public function debug($message, array $context = array())
    {
        if ($this->afterpayConfig->isDebugEnabled()) {
            return $this->_logger->debug($message, $context);
        }
    }
}