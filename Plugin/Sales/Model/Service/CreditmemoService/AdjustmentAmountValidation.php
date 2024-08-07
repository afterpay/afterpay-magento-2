<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Sales\Model\Service\CreditmemoService;

use Afterpay\Afterpay\Gateway\Config\Config;
use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

class AdjustmentAmountValidation
{
    private const ALLOWED_PAYMENT_STATES = [
        PaymentStateInterface::CAPTURED,
        PaymentStateInterface::PARTIALLY_CAPTURED
    ];

    public function beforeRefund(
        CreditmemoManagementInterface $subject,
        CreditmemoInterface           $creditmemo,
                                      $offlineRequested = false
    ) {
        $order = $creditmemo->getOrder();
        if (($creditmemo->getBaseAdjustmentPositive() != 0 || $creditmemo->getBaseAdjustmentNegative() != 0)
            && $order->getPayment()->getMethod() === Config::CODE
            && !in_array(
                $order->getPayment()->getAdditionalInformation(AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE),
                self::ALLOWED_PAYMENT_STATES
            )) {
            throw new LocalizedException(__(
                'You cannot use adjustments for a payment with a status'
                . ' that does not equal "CAPTURED" or "PARTIALLY_CAPTURED" for the current payment method.'
            ));
        }

        return [$creditmemo, $offlineRequested];
    }
}
