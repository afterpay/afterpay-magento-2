<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\Lib;

class WidgetCheckoutLibUrlProvider extends LibUrlProvider
{
    private $config;

    public function __construct(
        \Afterpay\Afterpay\Model\Url\UrlBuilder $urlBuilder,
        \Afterpay\Afterpay\Model\Config $config
    ) {
        parent::__construct($urlBuilder);
        $this->config = $config;
    }

    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Afterpay\Afterpay\Model\Url\UrlBuilder::TYPE_JS_LIB,
            'square-marketplace.js'
        );
    }
}
