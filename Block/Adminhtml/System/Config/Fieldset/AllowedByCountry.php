<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Fieldset;

class AllowedByCountry extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    private $afterpay;
    private $config;
    private $allowedCountriesConfigPath;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Payment\Model\MethodInterface $afterpay,
        \Afterpay\Afterpay\Model\Config $config,
        string $allowedCountriesConfigPath = '',
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->afterpay = $afterpay;
        $this->config = $config;
        $this->allowedCountriesConfigPath = $allowedCountriesConfigPath;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element): string
    {
        $allowedMerchantCountries = explode(',', $this->afterpay->getConfigData($this->allowedCountriesConfigPath));
        if (in_array($this->getMerchantCountry(), $allowedMerchantCountries)) {
            return parent::render($element);
        }
        return '';
    }

    private function getMerchantCountry(): ?string
    {
        /** @var \Magento\Config\Block\System\Config\Form $fieldSetForm */
        $fieldSetForm = $this->getForm();
        $scope = $fieldSetForm->getScope();
        $scopeCode = $fieldSetForm->getScopeCode();

        return $this->config->getMerchantCountry($scope, (int)$scopeCode);
    }
}
