<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\Payment;

use Magento\Sales\Model\Order\Payment;

class QuotePaidStorage
{
    private array $quotesOrderPayments = [];

    public function setAfterpayPaymentForQuote(int $quoteId, Payment $afterpayPayment): self
    {
        $this->quotesOrderPayments[$quoteId] = $afterpayPayment;
        return $this;
    }

    public function getAfterpayPaymentIfQuoteIsPaid(int $quoteId): ?Payment
    {
        return $this->quotesOrderPayments[$quoteId] ?? null;
    }
}
