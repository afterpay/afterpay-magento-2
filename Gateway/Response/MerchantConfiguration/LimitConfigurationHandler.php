<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

class LimitConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
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
        if (isset($response['maximumAmount']['amount'])) {
            $minimumAmount = $response['minimumAmount']['amount'] ?? "0";
            $minimumAmount = (string)max((float)$minimumAmount, 1);
            $maximumAmount = $response['maximumAmount']['amount'];
            $this->config
                ->setMinOrderTotal($minimumAmount, $websiteId)
                ->setMaxOrderTotal($maximumAmount, $websiteId);
        } else {
            $this->config
                ->deleteMaxOrderTotal($websiteId)
                ->deleteMinOrderTotal($websiteId);
        }
    }
}
