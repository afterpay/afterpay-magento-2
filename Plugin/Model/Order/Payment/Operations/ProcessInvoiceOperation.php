<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Model\Order\Payment\Operations;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment\Transaction;

class ProcessInvoiceOperation
{
    /**
     * @var \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface
     */
    private \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod;

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

    public function __construct(
        \Afterpay\Afterpay\Model\Checks\PaymentMethodInterface $checkPaymentMethod,
        \Magento\Sales\Model\Order\Payment\State\CommandInterface $stateCommand,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface $transactionManager,
        EventManagerInterface $eventManager
    ) {
        $this->checkPaymentMethod = $checkPaymentMethod;
        $this->stateCommand = $stateCommand;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionManager = $transactionManager;
        $this->eventManager = $eventManager;
    }

    public function aroundExecute(
        \Magento\Sales\Model\Order\Payment\Operations\ProcessInvoiceOperation $subject,
        \Closure $proceed,
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        \Magento\Sales\Api\Data\InvoiceInterface $invoice,
        string $operationMethod
    ) {
        if ($this->checkPaymentMethod->isAfterPayMethod($payment)
            && $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY)) {
            $amountToCapture = $payment->formatAmount($invoice->getGrandTotal(), true);
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
                throw new LocalizedException(
                    __('The transaction "%1" cannot be captured yet.', $invoice->getTransactionId())
                );
            }

            // attempt to capture: this can trigger "is_transaction_pending"
            $method = $payment->getMethodInstance();
            $method->setStore(
                $order->getStoreId()
            );

            $method->$operationMethod($payment, $amountToCapture);

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

        return $proceed($payment, $invoice, $operationMethod);
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