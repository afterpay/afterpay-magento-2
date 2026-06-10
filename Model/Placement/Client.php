<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Placement;

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
     * Pulls the placement data from the Placement API.
     *
     * @param string $mpid Merchant Public ID - Afterpay\Afterpay\Model\Config::getPublicId
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(string $mpid): array
    {
        $response = [];
        $actionUrl = $this->urlBuilder->build(UrlBuilder::TYPE_PLACEMENT_API, $mpid);
        try {
            $this->client->get($actionUrl);

            if (empty($this->client->getBody())) {
                return [];
            }

            if (is_string($this->client->getBody()) && strpos($this->client->getBody(), 'error code') !== false ) {
                $this->logger->critical("Afterpay: Error fetching placement data for mpid: {$mpid}");
                return [];
            }

            $unserializedBody = $this->serializer->unserialize($this->client->getBody());
            $response = is_array($unserializedBody) ? $unserializedBody : [];
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Please, try again';
            $this->logger->critical("Afterpay: Error fetching placement data for mpid: {$mpid}. " . $message, $e->getTrace());
        } finally {
            $this->debugLogger->debug([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'response' => $response
            ]);
        }
        return $response;
    }
}

