<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Model\Order\Payment\Operations;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class CaptureOperation
{
    /**
     * @var \Magento\Sales\Model\Order\Payment\State\CommandInterface
     */
    private $stateCommand;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $transactionBuilder;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface
     */
    private $transactionManager;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface
     */
    private $ckeckPaymentMethod;

    public function __construct(
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $ckeckPaymentMethod,
        \Magento\Sales\Model\Order\Payment\State\CommandInterface $stateCommand,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager,
        EventManagerInterface $eventManager
    ) {
        $this->ckeckPaymentMethod = $ckeckPaymentMethod;
        $this->stateCommand = $stateCommand;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionManager = $transactionManager;
        $this->eventManager = $eventManager;
    }

    public function aroundCapture(
        \Magento\Sales\Model\Order\Payment\Operations\CaptureOperation $subject,
        \Closure $proceed,
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        $invoice
    ) {
        if ($this->ckeckPaymentMethod->isAfterPayMethod($payment)
            && $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY)) {
            /**
             * @var $payment Payment
             */
            if (null === $invoice) {
                $invoice = $this->invoice($payment);
                $payment->setCreatedInvoice($invoice);
                if ($payment->getIsFraudDetected()) {
                    $payment->getOrder()->setStatus(Order::STATUS_FRAUD);
                }
                return $payment;
            }
            $amountToCapture = $payment->formatAmount($invoice->getGrandTotal());
            $order = $payment->getOrder();

            $payment->setTransactionId(
                $this->transactionManager->generateTransactionId(
                    $payment,
                    Transaction::TYPE_CAPTURE,
                    $payment->getAuthorizationTransaction()
                )
            );

            $this->eventManager->dispatch(
                'sales_order_payment_capture',
                ['payment' => $payment, 'invoice' => $invoice]
            );

            /**
             * Fetch an update about existing transaction. It can determine whether the transaction can be paid
             * Capture attempt will happen only when invoice is not yet paid and the transaction can be paid
             */
            if ($invoice->getTransactionId()) {
                $method = $payment->getMethodInstance();
                $method->setStore(
                    $order->getStoreId()
                );
                if ($method->canFetchTransactionInfo()) {
                    $method->fetchTransactionInfo(
                        $payment,
                        $invoice->getTransactionId()
                    );
                }
            }

            if ($invoice->getIsPaid()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The transaction "%1" cannot be captured yet.', $invoice->getTransactionId())
                );
            }

            // attempt to capture: this can trigger "is_transaction_pending"
            $method = $payment->getMethodInstance();
            $method->setStore(
                $order->getStoreId()
            );
            //TODO replace for sale usage
            $method->capture($payment, $amountToCapture);

            // prepare parent transaction and its amount
            $paidWorkaround = 0;
            if (!$invoice->wasPayCalled()) {
                $paidWorkaround = (double)$amountToCapture;
            }
            if ($payment->isCaptureFinal($paidWorkaround)) {
                $payment->setShouldCloseParentTransaction(true);
            }

            $transactionBuilder = $this->transactionBuilder->setPayment($payment);
            $transactionBuilder->setOrder($order);
            $transactionBuilder->setFailSafe(true);
            $transactionBuilder->setTransactionId($payment->getTransactionId());
            $transactionBuilder->setAdditionalInformation($payment->getTransactionAdditionalInfo());
            $transactionBuilder->setSalesDocument($invoice);
            $transaction = $transactionBuilder->build(Transaction::TYPE_CAPTURE);

            $message = $this->stateCommand->execute($payment, $amountToCapture, $order);
            if ($payment->getIsTransactionPending()) {
                $invoice->setIsPaid(false);
            } else {
                $invoice->setIsPaid(true);
                $this->updateTotals($payment, ['base_amount_paid_online' => $amountToCapture]);
            }
            $message = $payment->prependMessage($message);
            $payment->addTransactionCommentsToOrder($transaction, $message);
            $invoice->setTransactionId($payment->getLastTransId());

            return $payment;
        }

        return $proceed($payment, $invoice);
    }

    private function invoice(\Magento\Sales\Api\Data\OrderPaymentInterface $payment)
    {
        /** @var Invoice $invoice */
        $invoice = $payment->getOrder()->prepareInvoice();

        $invoice->register();
        if ($payment->getMethodInstance()->canCapture()) {
            $invoice->capture();
        }

        $payment->getOrder()->addRelatedObject($invoice);
        return $invoice;
    }

    private function updateTotals(\Magento\Sales\Api\Data\OrderPaymentInterface $payment, $data)
    {
        foreach ($data as $key => $amount) {
            if (null !== $amount) {
                $was = $payment->getDataUsingMethod($key);
                $payment->setDataUsingMethod($key, $was + $amount);
            }
        }
    }
}
