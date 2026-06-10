<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

class PlacementsConfigurationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Afterpay\Afterpay\Model\Config $config;
    private \Afterpay\Afterpay\Model\Placement\Service\GetPlacementData $getPlacementData;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\Placement\Service\GetPlacementData $getPlacementData
    ) {
        $this->config = $config;
        $this->getPlacementData = $getPlacementData;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $mpid = $response['publicId'] ?? '';
        $placemntsData = $this->getPlacementData->execute($mpid);
        foreach ($placemntsData as $placement) {
            switch ($placement['pageType']) {
                case 'product':
                    $this->config->setPlacementIdPdp($placement['placementId'], $websiteId);
                    break;
                case 'cart':
                    $this->config->setPlacementIdCart($placement['placementId'], $websiteId);
                    break;
            }
        }
    }
}
