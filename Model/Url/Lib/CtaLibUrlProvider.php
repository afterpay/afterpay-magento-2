<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\Lib;

class CtaLibUrlProvider extends LibUrlProvider
{
    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Afterpay\Afterpay\Model\Url\UrlBuilder::TYPE_JS_LIB,
            'afterpay-1.x.js'
        );
    }
}
