<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Payment;

class AfterpayDataProvider implements \Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface
{
    public function getData(array $data): array
    {
        if (!isset($data[$data['code']]['afterpay_token'])) {
            return [];
        }

        return $data[$data['code']];
    }
}
