<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Request;

class GetMerchantConfigurationDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(array $buildSubject): array
    {
        return [
            'websiteId' => $buildSubject['websiteId'],
        ];
    }
}
