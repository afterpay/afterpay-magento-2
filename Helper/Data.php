<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $_afterpayConfig;
    protected $_moduleList;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Afterpay\Afterpay\Model\Logger\Logger $logger,
        \Afterpay\Afterpay\Model\Config\Payovertime $afterpayConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->_afterpayConfig = $afterpayConfig;
        $this->_moduleList = $moduleList;
    }

    public function debug($message, array $context = [])
    {
        if ($this->_afterpayConfig->isDebugEnabled()) {
            return $this->_logger->debug($message, $context);
        }
    }

    public function getModuleVersion()
    {
        $moduleInfo = $this->_moduleList->getOne('Afterpay_Afterpay');
        return $moduleInfo['setup_version'];
    }
}
