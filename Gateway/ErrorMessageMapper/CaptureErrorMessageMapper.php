<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\ErrorMessageMapper;

use Afterpay\Afterpay\Gateway\Validator\CaptureResponseValidator;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class CaptureErrorMessageMapper implements ErrorMessageMapperInterface
{
    public function getMessage(string $code)
    {
        switch ($code) {
            case CaptureResponseValidator::STATUS_DECLINED:
                return __('Aftepay payment declined. Please select an alternative payment method.');
            default:
                return null;
        }
    }
}
