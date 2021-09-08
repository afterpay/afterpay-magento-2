<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $_afterpayConfig;
    protected $_moduleList;
    protected $_countryFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Afterpay\Afterpay\Model\Logger\Logger $logger,
        \Afterpay\Afterpay\Model\Config\Payovertime $afterpayConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
        $this->_afterpayConfig = $afterpayConfig;
        $this->_moduleList = $moduleList;
		$this->_countryFactory = $countryFactory;
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
	
	 public function getCbtCountry()
    {
		$cbtEnabled="Disabled";
        if($this->_afterpayConfig->isCbtEnabled()){
			$cbtEnabled = "Enabled";
			$cbtCountries = $this->_afterpayConfig->getCbtCountry();
			if(!empty($cbtCountries)){
				$cbtCountryCode=explode(",",$cbtCountries);
                $counrtyNames=[];
                foreach($cbtCountryCode AS $countryCode){
					if($country = $this->_countryFactory->create()->loadByCode($countryCode)){
						$counrtyNames[] = $country->getName();
					}
				}
				$cbtEnabled = $cbtEnabled." [ ".implode(" | ",$counrtyNames)." ]";
			}
		}
        return $cbtEnabled;
    }
	
	public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
