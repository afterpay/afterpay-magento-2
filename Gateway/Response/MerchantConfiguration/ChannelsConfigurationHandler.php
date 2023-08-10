<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

use Afterpay\Afterpay\Model\Config;
use Magento\Payment\Gateway\Response\HandlerInterface;

class ChannelsConfigurationHandler implements HandlerInterface
{
    private $config;

    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        if (isset($response['channels']) && is_array($response['channels'])) {

            $getCashAppConfig = array_search("CASH_APP", array_column($response['channels'], 'name'));
            $isCashAppEnabled = 0;
            if (isset($response['channels'][$getCashAppConfig])) {
                $cashappData=$response['channels'][$getCashAppConfig];
                if (isset($cashappData['enabled']) && $cashappData['enabled']==true &&
                    isset($cashappData['integrationCompleted']) && $cashappData['integrationCompleted']==true &&
                    isset($cashappData['enabledForOrders']) && $cashappData['enabledForOrders']==true) {
                    $isCashAppEnabled = (int)$cashappData['enabled'];
                }

                $this->config->setCashAppPayAvailable($isCashAppEnabled, $websiteId);
            }
        }
    }


}
