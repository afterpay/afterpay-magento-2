<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

class CBTAvailableCurrenciesConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private $config;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $cbtAvailableCurrencies = [];
        if (isset($response['CBT']['enabled']) &&
            isset($response['CBT']['limits']) &&
            is_array($response['CBT']['limits'])
        ) {
            foreach ($response['CBT']['limits'] as $limit) {
                if (isset($limit['maximumAmount']['currency']) && isset($limit['maximumAmount']['amount'])) {
                    $cbtAvailableCurrencies[] = $limit['maximumAmount']['currency'] . ':' . $limit['maximumAmount']['amount'];
                }
            }
        }
        $this->config->setCbtCurrencyLimits(implode(",", $cbtAvailableCurrencies), $websiteId);
    }
}
