<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Status;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

class StatusChangerTest extends \PHPUnit\Framework\TestCase
{
    private \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoProcessor $creditMemoProcessor;
    /**
     * @var \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate|\PHPUnit\Framework\MockObject\MockObject
     */
    private $expiryDate;
    /**
     * @var \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoInitiator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditmemoInitiator;
    /**
     * @var \Magento\Sales\Api\CreditmemoManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditmemoManager;
    /**
     * @var \Afterpay\Afterpay\Model\Order\CreditMemo\OrderUpdater|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderUpdater;
    /**
     * @var \Afterpay\Afterpay\Model\Order\CreditMemo\PaymentUpdater|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentUpdater;
    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private $order;
    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentInfoInterface;
    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $payment;
    /**
     * @var \Magento\Sales\Model\Order\Creditmemo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditmemo;

    public function setUp(): void
    {
        $this->expiryDate = $this->createMock(
            \Afterpay\Afterpay\Model\Order\Payment\Auth\ExpiryDate::class
        );
        $this->creditmemoInitiator = $this->createMock(
            \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoInitiator::class
        );
        $this->creditmemoManager = $this->createMock(
            \Magento\Sales\Api\CreditmemoManagementInterface::class
        );
        $this->orderUpdater = $this->createMock(
            \Afterpay\Afterpay\Model\Order\CreditMemo\OrderUpdater::class
        );
        $this->paymentUpdater = $this->createMock(
            \Afterpay\Afterpay\Model\Order\CreditMemo\PaymentUpdater::class
        );
        $this->creditMemoProcessor = new  \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoProcessor(
            $this->expiryDate,
            $this->creditmemoInitiator,
            $this->creditmemoManager,
            $this->orderUpdater,
            $this->paymentUpdater
        );
        $this->order = $this->createMock(
            \Magento\Sales\Model\Order::class
        );
        $this->payment = $this->createMock(
            \Magento\Sales\Api\Data\OrderPaymentInterface::class
        );
        $this->paymentInfoInterface = $this->createMock(
            \Magento\Payment\Model\InfoInterface::class
        );
        $this->creditmemo = $this->createMock(
            \Magento\Sales\Model\Order\Creditmemo::class
        );
    }

    /**
     * @dataProvider dataForProcessOrder
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataForProcessOrder')]
    public function testExecute(bool $isExpired, array $expectedData, array $updatedAdditionalInfo)
    {
        $this->order
            ->method("getData")
            ->with('additional_information')
            ->willReturn(
                [
                    AdditionalInformationInterface::AFTERPAY_AUTH_EXPIRY_DATE => '
                    In this test the date does not matter because we have tested it in ExpiryDateTest.
                Is date expired passes as argument'
                ]
            );
        $this->expiryDate
            ->expects($this->atLeastOnce())
            ->method("isExpired")
            ->willReturn($isExpired);
        $this->order
            ->expects($this->exactly($expectedData["orderInvocation"]))
            ->method("getPayment")
            ->willReturn(
                $this->paymentInfoInterface
            );
        $this->payment
            ->expects($this->exactly($expectedData["paymentInvocation"]))
            ->method("getAdditionalInformation")
            ->willReturn($updatedAdditionalInfo);
        $this->paymentUpdater
            ->expects($this->exactly($expectedData["paymentUpdaterInvocation"]))
            ->method("updatePayment")
            ->willReturn($this->payment);
        $this->creditmemoInitiator
            ->expects($this->exactly($expectedData["creditmemoInitiatorInvocation"]))
            ->method("init")
            ->willReturn($this->creditmemo);
        $this->creditmemoManager
            ->expects($this->exactly($expectedData["creditmemoManagerInvocation"]))
            ->method("refund");
        $this->orderUpdater
            ->expects($this->exactly($expectedData["orderUpdaterInvocation"]))
            ->method("updateOrder");
        $this->creditMemoProcessor->processOrder($this->order);
    }

    public static function dataForProcessOrder():array
    {
        return [
            [
                false,
                [
                    "orderInvocation" => 0,
                    "paymentInvocation" => 0,
                    "paymentUpdaterInvocation" => 0,
                    "creditmemoInitiatorInvocation" => 0,
                    "creditmemoManagerInvocation" => 0,
                    "orderUpdaterInvocation" => 0
                ],
                []
            ],
            [
                true,
                [
                    "orderInvocation" => 1,
                    "paymentInvocation" => 1,
                    "paymentUpdaterInvocation" => 1,
                    "creditmemoInitiatorInvocation" => 0,
                    "creditmemoManagerInvocation" => 0,
                    "orderUpdaterInvocation" => 0
                ],
                [
                    AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE => 'NOT RIGHT'
                ]
            ],
            [
                true,
                [
                    "orderInvocation" => 1,
                    "paymentInvocation" => 1,
                    "paymentUpdaterInvocation" => 1,
                    "creditmemoInitiatorInvocation" => 1,
                    "creditmemoManagerInvocation" => 1,
                    "orderUpdaterInvocation" => 1
                ],
                [
                    AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE => PaymentStateInterface::CAPTURED
                ]
            ],
            [
                true,
                [
                    "orderInvocation" => 1,
                    "paymentInvocation" => 1,
                    "paymentUpdaterInvocation" => 1,
                    "creditmemoInitiatorInvocation" => 1,
                    "creditmemoManagerInvocation" => 1,
                    "orderUpdaterInvocation" => 1
                ],
                [
                    AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE => PaymentStateInterface::VOIDED
                ]
            ]
        ];
    }
}
