<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\Lib;

class ExpressCheckoutLibUrlProvider extends LibUrlProvider
{
    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Afterpay\Afterpay\Model\Url\UrlBuilder::TYPE_JS_LIB,
            'square-marketplace.js'
        );
    }
}
