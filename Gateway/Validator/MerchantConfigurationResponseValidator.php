<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Validator;

class MerchantConfigurationResponseValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    public function validate(array $validationSubject): \Magento\Payment\Gateway\Validator\ResultInterface
    {
        $response = \Magento\Payment\Gateway\Helper\SubjectReader::readResponse($validationSubject);

        if (isset($response['errorCode'])) {
            return $this->createResult(false, [], [$response['errorCode']]);
        }

        return $this->createResult(true);
    }
}
