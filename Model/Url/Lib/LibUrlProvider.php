<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\Lib;

abstract class LibUrlProvider
{
    protected \Afterpay\Afterpay\Model\Url\UrlBuilder $urlBuilder;
    protected static bool $isLibGotten = false;

    public function __construct(
        \Afterpay\Afterpay\Model\Url\UrlBuilder $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getAfterpayLib(): ?string
    {
        if (!self::$isLibGotten) {
            self::$isLibGotten = true;
            return $this->buildUrl();
        }
        return null;
    }

    public function getIsLibGotten(): bool
    {
        return self::$isLibGotten;
    }

    abstract protected function buildUrl(): string;
}
