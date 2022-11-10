<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

class DiscountHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $totalDiscount = $this->getOrderDiscountAmount($payment->getOrder());
        $paymentState = $payment->getAdditionalInformation(AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE);
        if ($paymentState == PaymentStateInterface::CAPTURED) {
            $rolloverDiscount = '0.00';
            $capturedDiscount = $totalDiscount;
        } else {
            $rolloverDiscount = $totalDiscount;
            $capturedDiscount = '0.00';
        }
        $payment->setAdditionalInformation(
            AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT,
            $rolloverDiscount
        );
        $payment->setAdditionalInformation(
            AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT,
            $capturedDiscount
        );
    }

    protected function getOrderDiscountAmount(\Magento\Sales\Model\Order $order): float
    {
        $isCBTCurrency = (bool) $order->getPayment()->getAdditionalInformation(
            \Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY
        );

        if ($isCBTCurrency) {
            return (float)($order->getGiftCardsAmount() + $order->getCustomerBalanceAmount());
        } else {
            return (float)($order->getBaseGiftCardsAmount() + $order->getBaseCustomerBalanceAmount());
        }
    }
}
