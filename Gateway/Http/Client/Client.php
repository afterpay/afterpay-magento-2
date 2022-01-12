<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Http\Client;

class Client implements \Magento\Payment\Gateway\Http\ClientInterface
{
    protected \Magento\Framework\HTTP\ClientInterface $client;
    protected \Magento\Framework\Serialize\SerializerInterface $serializer;
    private \Psr\Log\LoggerInterface $logger;
    private \Magento\Payment\Model\Method\Logger $debugLogger;
    private \Magento\Framework\App\RequestInterface $request;

    public function __construct(
        \Magento\Framework\HTTP\ClientInterface $client,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Model\Method\Logger $debugLogger,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->debugLogger = $debugLogger;
        $this->request = $request;
    }

    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject): array
    {
        $response = [];
        try {
            $response = $this->process($transferObject);
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Please, try again';
            $this->logger->critical($message, $e->getTrace());
            throw new \Magento\Payment\Gateway\Http\ClientException(__($message));
        } finally {
            $this->debugLogger->debug([
                'merchant_id' => $transferObject->getAuthUsername(),
                'merchant_action' => $this->request instanceof \Magento\Framework\App\Request\Http
                    ? $this->request->getServer('REQUEST_URI') : '',
                'target_uri' => $transferObject->getUri(),
                'request_body' => $transferObject->getBody(),
                'response' => $response
            ]);
        }
        return $response;
    }

    protected function process(\Magento\Payment\Gateway\Http\TransferInterface $transferObject): array
    {
        $this->client->setHeaders($transferObject->getHeaders());
        $this->client->addHeader(
            'Authorization',
            'Basic ' . base64_encode($transferObject->getAuthUsername() . ':' . $transferObject->getAuthPassword())
        );
        if ($transferObject->getMethod() == \Afterpay\Afterpay\Gateway\Http\TransferFactory::METHOD_POST) {
            $body = $transferObject->getBody();
            if (is_array($body) && !empty($body)) {
                $body = (string)$this->serializer->serialize($transferObject->getBody());
            }
            $this->client->post($transferObject->getUri(), $body ?: []);
        } else {
            $this->client->get($transferObject->getUri());
        }
        $unserializedBody = $this->serializer->unserialize($this->client->getBody());
        return is_array($unserializedBody) ? (array)$unserializedBody : [];
    }
}
