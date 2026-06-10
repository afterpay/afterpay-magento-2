<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Placement\Service;

use Magento\Framework\Exception\LocalizedException;

class GetPlacementData
{
    private \Afterpay\Afterpay\Model\Placement\Client $client;

    public function __construct(
        \Afterpay\Afterpay\Model\Placement\Client $client
    ) {
        $this->client = $client;
    }

    /**
     * Get placements data.
     *
     * @param string $mpid
     * @return array
     * @throws LocalizedException
     */
    public function execute(string $mpid): array
    {
        return $this->getOnsitePlacements($mpid);
    }

    /**
     * Pulls the placement data from the Placement API.
     *
     * @param string $mpid Merchant Public ID - Afterpay\Afterpay\Model\Config::getPublicId
     *
     * @return array
     * @throws LocalizedException
     */
    private function doRequest(string $mpid): array
    {
        $response = $this->client->execute($mpid);

        return $response['mcrResponse']['data']['config']['config'] ?? [];
    }

    /**
     * Get onsite placements configuration
     *
     * @param string $mpid Merchant Public ID
     * @return array
     * @throws LocalizedException
     */
    private function getOnsitePlacements(string $mpid): array
    {
        $config = $this->doRequest($mpid);
        return $config['onsitePlacements']['details']['onsitePlacements'] ?? [];
    }
}

