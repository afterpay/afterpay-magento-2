<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Fieldset;

class Payment extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    private \Magento\Payment\Model\MethodInterface $afterpay;
    private \Afterpay\Afterpay\Model\Config $config;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer,
        \Magento\Payment\Model\MethodInterface $afterpay,
        \Afterpay\Afterpay\Model\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
        $this->afterpay = $afterpay;
        $this->config = $config;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element): string
    {
        $allowedMerchantCountries = explode(',', $this->afterpay->getConfigData('allowed_merchant_countries'));
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

        if ($countryCode = $this->getRequest()->getParam('paypal_country')) {
            return $countryCode;
        }

        return $this->config->getMerchantCountry($scope, (int)$scopeCode);
    }
}
