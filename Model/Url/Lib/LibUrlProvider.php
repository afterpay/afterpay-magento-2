<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Url\Lib;

abstract class LibUrlProvider
{
    protected $urlBuilder;
    protected $isLibGotten = false;

    public function __construct(
        \Afterpay\Afterpay\Model\Url\UrlBuilder $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getAfterpayLib(): ?string
    {
        if (!$this->isLibGotten) {
            $this->isLibGotten = true;
            return $this->buildUrl();
        }
        return null;
    }

    public function getIsLibGotten(): bool
    {
        return $this->isLibGotten;
    }

    abstract protected function buildUrl(): string;
}
