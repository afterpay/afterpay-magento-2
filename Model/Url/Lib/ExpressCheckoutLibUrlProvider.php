<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\Lib;

class ExpressCheckoutLibUrlProvider extends LibUrlProvider
{
    protected function buildUrl(): string
    {
        return $this->urlBuilder->build(
            \Afterpay\Afterpay\Model\Url\UrlBuilder::TYPE_WEB_JS_LIB,
            'afterpay.js?merchant_key=magento2'
        );
    }
}
