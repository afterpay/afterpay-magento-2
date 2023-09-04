<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    private \Magento\Framework\Locale\Resolver $localeResolver;

    private \Magento\Checkout\Model\Session $checkoutSession;

    private \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability;
    private Config $config;

    public function __construct(
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface $checkCBTCurrencyAvailability,
        \Afterpay\Afterpay\Model\Config $config
    ) {
        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
        $this->config = $config;
    }

    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();

        return [
            'payment' => [
                'afterpay' => [
                    'locale' => $this->localeResolver->getLocale(),
                    'isCBTCurrency' => $this->checkCBTCurrencyAvailability->checkByQuote($quote),
                    'consumerLendingEnabled' => $this->config->getConsumerLendingEnabled(),
                    'consumerLendingMinimumAmount' => $this->config->getConsumerLendingMinAmount(),
                ]
            ]
        ];
    }
}
