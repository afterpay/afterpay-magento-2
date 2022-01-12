<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\ErrorMessageMapper;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class MerchantConfigurationErrorMessageMapper implements ErrorMessageMapperInterface
{
    public function getMessage(string $code)
    {
        switch ($code) {
            case 'unauthorized':
                return __('Afterpay merchant configuration fetching is failed. Wrong credentials.');
            default:
                return __('Afterpay merchant configuration fetching is failed. See logs.');
        }
    }
}
