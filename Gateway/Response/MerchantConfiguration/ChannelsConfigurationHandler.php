<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

use Afterpay\Afterpay\Model\Config;
use Magento\Payment\Gateway\Response\HandlerInterface;

class ChannelsConfigurationHandler implements HandlerInterface
{
    private Config $config;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];

        if (isset($response['channels']) && is_array($response['channels'])) {
            $getCashAppConfig = array_search("CASH_APP", array_column($response['channels'], 'name'));
            $isCashAppEnabled = 0;

            if ( isset($response['channels'][$getCashAppConfig]) &&
                isset($response['channels'][$getCashAppConfig]['name']) &&
                strtoupper($response['channels'][$getCashAppConfig]['name'])== "CASH_APP") {
                $cashappData=$response['channels'][$getCashAppConfig];

                if (isset($cashappData['enabled']) && $cashappData['enabled']==true &&
                    isset($cashappData['integrationCompleted']) && $cashappData['integrationCompleted']==true &&
                    isset($cashappData['enabledForOrders']) && $cashappData['enabledForOrders']==true) {
                    $isCashAppEnabled = (int)$cashappData['enabled'];
                }

            }else{
                if( $this->config->getCashAppPayActive($websiteId)==true) {
                    //Disable the Cash App Pay if it's not available for the Merchant
                    $this->config->setCashAppPayActive($isCashAppEnabled, $websiteId);
                    $this->logger->notice("Disable the Cash App Pay as it's not available for the Merchant");
                }
            }
            $this->config->setCashAppPayAvailable($isCashAppEnabled, $websiteId);
        }
    }
}
