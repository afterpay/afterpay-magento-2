<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Order\Payment\State;

use Magento\Sales\Model\Order;

/**
 * Changes order status state history for Afterpay order(deffered flow).
 */
class CaptureCommand
{
    private \Magento\Sales\Model\Order\StatusResolver $statusResolver;
    private \Afterpay\Afterpay\Model\Config $config;

    public function __construct(
        \Magento\Sales\Model\Order\StatusResolver $statusResolver,
        \Afterpay\Afterpay\Model\Config           $config
    ) {
        $this->statusResolver = $statusResolver;
        $this->config = $config;
    }

    public function aroundExecute(
        \Magento\Sales\Model\Order\Payment\State\CaptureCommand $subject,
        callable                                                $proceed,
        \Magento\Sales\Api\Data\OrderPaymentInterface           $payment,
                                                                $amount,
        \Magento\Sales\Api\Data\OrderInterface                  $order
    ): \Magento\Framework\Phrase {
        if ($payment->getMethod() === \Afterpay\Afterpay\Gateway\Config\Config::CODE) {
            $state = Order::STATE_PROCESSING;
            $status = null;
            $message = $this->config->getPaymentFlow() == \Afterpay\Afterpay\Model\Config\Source\PaymentFlow::DEFERRED ?
                'Authorized and open to capture amount of %1 online.' :
                'Captured amount of %1 online.';

            if ($payment->getIsTransactionPending()) {
                $state = Order::STATE_PAYMENT_REVIEW;
                $message = 'An amount of %1 will be captured after being approved at the payment gateway.';
            }

            if ($payment->getIsFraudDetected()) {
                $state = Order::STATE_PAYMENT_REVIEW;
                $status = Order::STATUS_FRAUD;
                $message .= ' Order is suspended as its capturing amount %1 is suspected to be fraudulent.';
            }

            if (!isset($status)) {
                $status = $this->statusResolver->getOrderStatusByState($order, $state);
            }

            $order->setState($state);
            $order->setStatus($status);

            return __($message, $order->getOrderCurrency()->formatTxt($amount));
        }

        return $proceed($payment, $amount, $order);
    }
}
