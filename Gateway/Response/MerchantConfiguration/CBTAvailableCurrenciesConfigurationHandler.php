<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

use Afterpay\Afterpay\Model\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CBTAvailableCurrenciesConfigurationHandler implements HandlerInterface
{
    private Config $config;
    private SerializerInterface $serializer;

    public function __construct(
        Config              $config,
        SerializerInterface $serializer
    ) {
        $this->config = $config;
        $this->serializer = $serializer;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $cbtAvailableCurrencies = '';

        if (isset($response['CBT']['enabled']) &&
            isset($response['CBT']['limits']) &&
            is_array($response['CBT']['limits'])
        ) {
            $cbtAvailableCurrencies = $this->serializer->serialize($response['CBT']['limits']);
        }

        $this->config->setCbtCurrencyLimits($cbtAvailableCurrencies, $websiteId);
    }
}
