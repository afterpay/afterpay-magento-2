<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Status;

use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

class OrderUpdaterTest extends \PHPUnit\Framework\TestCase
{
    private $orderUpdater;
    private $orderRepository;

    public function setUp(): void
    {
        $this->orderRepository = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderUpdater = new \Afterpay\Afterpay\Model\Order\CreditMemo\OrderUpdater($this->orderRepository);
    }

    /**
     * @dataProvider dataToStatusChanger
     */
    public function testExecute(array $additionalPaymentInformation, ?string $expectedOrderState)
    {
        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payment = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payment->expects($this->once())->method("getAdditionalInformation")->willReturn($additionalPaymentInformation);
        $order->expects($this->once())->method("getPayment")->willReturn($payment);
        if ($expectedOrderState != null) {
            $order->expects($this->once())->method("setState")->with($expectedOrderState);
            $order->expects($this->once())->method("setStatus")->with($expectedOrderState);
        } else {
            $order->expects($this->never())->method("setState");
            $order->expects($this->never())->method("setStatus");
        }
        $this->orderUpdater->updateOrder($order);
    }

    public function dataToStatusChanger()
    {
        return [
            [
                [
                    AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE => PaymentStateInterface::CAPTURED],
                \Magento\Sales\Model\Order::STATE_COMPLETE
            ],
            [
                [
                    AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE => PaymentStateInterface::VOIDED
                ],
                null
            ],
            ];
    }
}
