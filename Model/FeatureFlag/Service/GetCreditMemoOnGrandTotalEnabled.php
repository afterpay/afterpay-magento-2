<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Model\FeatureFlag\Service;

use Magento\Framework\Exception\LocalizedException;

class GetCreditMemoOnGrandTotalEnabled
{
    private const CREDIT_MEMP_ON_GRAND_TOTAL_ENABLED_FLAG = 'credit-memo-on-grand-total-enabled';

    private \Afterpay\Afterpay\Model\FeatureFlag\Client $client;

    public function __construct(\Afterpay\Afterpay\Model\FeatureFlag\Client  $client)
    {
        $this->client = $client;
    }

    /**
     * Pulls the "credit-memo-on-grand-total-enabled" feature flag value from the internal API.
     *
     * @param string $mpid        Merchant Public ID - Afterpay\Afterpay\Model\Config::getPublicId
     * @param string $countryCode Merchant Country ID - Afterpay\Afterpay\Model\Config::getMerchantCountry
     *
     * @return bool
     * @throws LocalizedException
     */
    public function execute(string $mpid, string $countryCode): bool
    {
        $response = $this->client->execute(self::CREDIT_MEMP_ON_GRAND_TOTAL_ENABLED_FLAG, $mpid, $countryCode);

        return $response['value'] ?? false;
    }
}
