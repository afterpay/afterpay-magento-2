<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

class ConsumerLendingConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Afterpay\Afterpay\Model\Config $config;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $consumerLending = $response['consumerLending']['enabled'] ?? false;
        $this->config->setConsumerLendingEnabled((int)$consumerLending, $websiteId);
        if ($consumerLending) {
            $minAmount = $response['consumerLending']['minimumAmount']['amount'] ?? null;
            $this->config->setConsumerLendingMinAmount($minAmount, $websiteId);
        }
    }
}
