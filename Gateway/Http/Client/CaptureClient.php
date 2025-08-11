<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Http\Client;

class CaptureClient extends Client
{
    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject): array
    {
        try {
            $response = parent::placeRequest($transferObject);
            if (empty($response)) {
                $response = $this->getPaymentByToken($transferObject);
            }
        } catch (\Throwable $e) {
            $response = $this->getPaymentByToken($transferObject);
        }
        return $response;
    }

    private function getPaymentByToken(\Magento\Payment\Gateway\Http\TransferInterface $transferObject): array
    {
        $this->logger->warning(
            'Something went wrong during the execution of the capture transaction. getPaymentByToken() is called'
        );
        try {
            if (empty($this->client->getBody()) || strpos($transferObject->getUri(), 'capture') === false) {
                return [];
            }

            $token = $transferObject->getBody()['token'] ?? null;
            if (!$token) {
                return [];
            }

            $this->client->setHeaders($transferObject->getHeaders());
            $this->client->addHeader(
                'Authorization',
                'Basic ' . base64_encode($transferObject->getAuthUsername() . ':' . $transferObject->getAuthPassword())
            );

            $uri = str_replace('capture', 'token:', $transferObject->getUri());
            $this->client->get($uri . $token);
            $unserializedBody = $this->serializer->unserialize($this->client->getBody());

            $this->debugLogger->debug([
                'merchant_id'     => $transferObject->getAuthUsername(),
                'merchant_action' => $this->request instanceof \Magento\Framework\App\Request\Http
                    ? $this->request->getServer('REQUEST_URI') : '',
                'target_uri'      => $uri,
                'request_body'    => [],
                'response'        => $unserializedBody
            ]);

            return is_array($unserializedBody) ? $unserializedBody : [];
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Please, try again';
            $this->logger->critical($message, $e->getTrace());
            throw new \Magento\Payment\Gateway\Http\ClientException(__($message));
        }
    }
}
