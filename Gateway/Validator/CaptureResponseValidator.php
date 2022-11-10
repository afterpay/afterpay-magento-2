<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Validator;

class CaptureResponseValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    const STATUS_DECLINED = 'DECLINED';
    const STATUS_APPROVED = 'APPROVED';

    public function validate(array $validationSubject): \Magento\Payment\Gateway\Validator\ResultInterface
    {
        $response = \Magento\Payment\Gateway\Helper\SubjectReader::readResponse($validationSubject);

        if (isset($response['status']) && $response['status'] == self::STATUS_DECLINED) {
            return $this->createResult(
                false,
                [
                __('%1 payment declined. Please select an alternative payment method.',
                    ($validationSubject['payment'])->getPayment()->getMethodInstance()->getTitle())
                ]
            );
        }

        if (isset($response['status']) && $response['status'] == self::STATUS_APPROVED) {
            return $this->createResult(true);
        }

        if (isset($response['errorCode'])) {
            return $this->createResult(false, [$response['message']], [$response['errorCode']]);
        }

        return $this->createResult(false, ['Unknown status has been returned']);
    }
}
