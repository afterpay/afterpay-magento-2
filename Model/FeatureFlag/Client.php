<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\FeatureFlag;

use Afterpay\Afterpay\Model\Url\UrlBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Model\Method\Logger;
use Psr\Log\LoggerInterface;

class Client
{
    private ClientInterface $client;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private Logger $debugLogger;
    private UrlBuilder $urlBuilder;

    public function __construct(
        ClientInterface $client,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        Logger $debugLogger,
        UrlBuilder $urlBuilder
    ) {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->debugLogger = $debugLogger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Pulls the feature flag data from the internal API.
     *
     * @param string $flag        Feature flag name
     * @param string $mpid        Merchant Public ID - Afterpay\Afterpay\Model\Config::getPublicId
     * @param string $countryCode Merchant Country ID - Afterpay\Afterpay\Model\Config::getMerchantCountry
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(string $flag, string $mpid, string $countryCode): array
    {
        $response = [];
        $actionUrl = $this->urlBuilder->build(UrlBuilder::TYPE_FEATURE_FLAG_API, $flag);
        try {
            $this->client->addHeader('x-mpid', $mpid);
            $this->client->addHeader('x-country', $countryCode);
            $this->client->get($actionUrl);

            if (empty($this->client->getBody())) {
                return [];
            }

            if (is_string($this->client->getBody()) && strpos($this->client->getBody(), 'error code') !== false ) {
                $this->logger->critical("Afterpay: Error fetching feature flag: {$flag}");
                return [];
            }

            $unserializedBody = $this->serializer->unserialize($this->client->getBody());
            $response = is_array($unserializedBody) ? $unserializedBody : [];
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Please, try again';
            $this->logger->critical("Afterpay: Error fetching feature flag: {$flag}" . $message, $e->getTrace());
        } finally {
            $this->debugLogger->debug([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'request_flag' => $flag,
                'response' => $response
            ]);
        }
        return $response;
    }
}
