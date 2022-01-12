<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Order\Payment;

class QuotePaidStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testStorageSetGet()
    {
        $testQuoteId = 1;
        $testPaymentId = 333;
        $testAfterpayOrderPaymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $testAfterpayOrderPaymentMock->expects($this->any())
            ->method('getId')
            ->willReturn($testPaymentId);

        $quotePaidStorage = new \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage();
        $quotePaidStorage->setAfterpayPaymentForQuote($testQuoteId, $testAfterpayOrderPaymentMock);
        static::assertSame(
            $testAfterpayOrderPaymentMock->getId(),
            $quotePaidStorage->getAfterpayPaymentIfQuoteIsPaid($testQuoteId)->getId()
        );
    }

    public function testEmptyPayment()
    {
        $testUnexistedQuoteId = 99;

        $quotePaidStorage = new \Afterpay\Afterpay\Model\Order\Payment\QuotePaidStorage();
        static::assertSame($quotePaidStorage->getAfterpayPaymentIfQuoteIsPaid($testUnexistedQuoteId), null);
    }
}
