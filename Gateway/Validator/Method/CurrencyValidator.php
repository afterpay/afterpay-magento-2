<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Validator\Method;

class CurrencyValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    private $config;
    private $checkoutSession;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Afterpay\Afterpay\Model\Config $config
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($resultFactory);
    }

    public function validate(array $validationSubject): \Magento\Payment\Gateway\Validator\ResultInterface
    {
        $quote = $this->checkoutSession->getQuote();
        $currentCurrency = $quote->getQuoteCurrencyCode();
        $allowedCurrencies = $this->config->getAllowedCurrencies();
        $cbtCurrencies = array_keys($this->config->getCbtCurrencyLimits());

        if (in_array($currentCurrency, array_merge($allowedCurrencies, $cbtCurrencies))) {
            return $this->createResult(true);
        }

        return $this->createResult(false);
    }
}
