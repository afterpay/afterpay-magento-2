<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Http;

use Afterpay\Afterpay\Model\Url\UrlBuilder;

class TransferFactory implements \Magento\Payment\Gateway\Http\TransferFactoryInterface
{
    const METHOD_POST = "post";
    const METHOD_GET = "get";

    const ARGS = [
        'orderId',
        'websiteId',
        'storeId',
        'afterpayToken'
    ];

    private \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder;
    private \Afterpay\Afterpay\Model\Config $config;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private TransferFactory\UserAgentProvider $userAgentProvider;
    private UrlBuilder $urlBuilder;
    private string $uriPath;
    private string $method;

    public function __construct(
        \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder,
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TransferFactory\UserAgentProvider $userAgentProvider,
        UrlBuilder $urlBuilder,
        string $uriPath,
        string $method = self::METHOD_POST
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->userAgentProvider = $userAgentProvider;
        $this->urlBuilder = $urlBuilder;
        $this->uriPath = $uriPath;
        $this->method = $method;
    }

    public function create(array $request): \Magento\Payment\Gateway\Http\TransferInterface
    {
        $args = $this->removeAndReturnArgs($request);

        $websiteId = (int)($args['websiteId'] ??
            $this->storeManager->getStore($args['storeId'])->getWebsiteId());

        return $this->transferBuilder
            ->setAuthUsername($this->config->getMerchantId($websiteId))
            ->setAuthPassword($this->config->getMerchantKey($websiteId))
            ->setHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => $this->userAgentProvider->provide($websiteId)
            ])
            ->setUri($this->urlBuilder->build(UrlBuilder::TYPE_API, $this->uriPath, $args))
            ->setMethod($this->method)
            ->setBody($request)
            ->build();
    }

    private function removeAndReturnArgs(array &$request): array
    {
        $argsToReturn = [];
        foreach (static::ARGS as $arg) {
            if (isset($request[$arg])) {
                $argsToReturn[$arg] = $request[$arg];
                unset($request[$arg]);
            }
        }
        return $argsToReturn;
    }
}
