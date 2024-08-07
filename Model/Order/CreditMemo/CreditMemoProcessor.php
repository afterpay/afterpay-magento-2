<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

class CreditMemoProcessor
{
    private $expiryDate;
    private $creditMemoInitiator;
    private $creditmemoManagement;
    private $orderUpdater;
    private $paymentUpdater;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate $expiryDate,
        \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoInitiator $creditMemoInitiator,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        OrderUpdater $orderUpdater,
        PaymentUpdater $paymentUpdater
    ) {
        $this->expiryDate = $expiryDate;
        $this->creditMemoInitiator = $creditMemoInitiator;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->orderUpdater = $orderUpdater;
        $this->paymentUpdater = $paymentUpdater;
    }

    public function processOrder(\Magento\Sales\Model\Order $order): void
    {
        $additionalInformation = $order->getData('additional_information');
        $expireDate = $additionalInformation[AdditionalInformationInterface::AFTERPAY_AUTH_EXPIRY_DATE] ?? null;
        if (!$expireDate || !$this->expiryDate->isExpired($expireDate)) {
            return;
        }
        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $order->getPayment();
        $payment = $this->paymentUpdater->updatePayment($payment);
        $additionalInformation = $payment->getAdditionalInformation();
        $paymentState = $additionalInformation[
            AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE
        ];
        if ($paymentState !== PaymentStateInterface::CAPTURED &&
            $paymentState !== PaymentStateInterface::PARTIALLY_CAPTURED &&
            $paymentState !== PaymentStateInterface::VOIDED &&
            $paymentState !== PaymentStateInterface::EXPIRED) {
            return;
        }
        $creditmemo = $this->creditMemoInitiator->init($order);
        $this->creditmemoManagement->refund($creditmemo, true);
        $this->orderUpdater->updateOrder($order);
    }
}
