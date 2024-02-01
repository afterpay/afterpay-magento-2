<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Payment\AmountProcessor;

use Afterpay\Afterpay\Model\Payment\AmountProcessor\CreditMemo as CreditMemoAmountProcessor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CreditMemoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $payment;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditMemo;

    /**
     * @var object
     */
    private $creditMemoAmountProcessor;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private $order;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->order = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->creditMemo = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $this->creditMemoAmountProcessor = $this->objectManager->getObject(CreditMemoAmountProcessor::class);
    }

    public function testAdjustmentAmountWithoutAnyReturnedAmount()
    {
        $this->initPaymentObject();
        $this->assertEquals([0, 0.1], $this->creditMemoAmountProcessor->process($this->payment));
    }

    private function initPaymentObject(): void
    {
        $this->prepareCreditMemoObject();
        $this->prepareOrderObject();

        $this->payment->expects($this->any())->method("getCreditmemo")
            ->with()
            ->willReturn($this->creditMemo);
        $this->payment->expects($this->any())->method("getOrder")
            ->with()
            ->willReturn($this->order);
        $this->payment->expects($this->any())->method("getAdditionalInformation")
            ->with()
            ->willReturn([
                \Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE
                => \Afterpay\Afterpay\Model\PaymentStateInterface::AUTH_APPROVED
            ]);
    }

    private function prepareCreditMemoObject(): void
    {
        $this->creditMemo->expects($this->any())->method("getAllItems")
            ->with()
            ->willReturn($this->getCreditMemoItems());
        $this->creditMemo->expects($this->any())->method("getAdjustmentPositive")
            ->with()
            ->willReturn(0.25);
        $this->creditMemo->expects($this->any())->method("getAdjustmentNegative")
            ->with()
            ->willReturn(0.15);
    }

    private function prepareOrderObject(): void
    {
        $shipmentCollection = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['count'])
            ->getMock();
        $shipmentCollection->expects($this->atLeastOnce())->method('count')->willReturn(0);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->any())->method("getWebsiteId")->willReturn(1);

        $this->order->expects($this->any())->method("getStore")
            ->with()
            ->willReturn($store);
        $this->order->expects($this->any())->method("getPayment")
            ->with()
            ->willReturn($this->payment);
        $this->order->expects($this->any())->method("getShipmentsCollection")
            ->with()
            ->willReturn($shipmentCollection);
    }

    private function getCreditMemoItems(): array
    {
        return [
            $this->itemFactory(\Magento\Sales\Model\Order\Creditmemo\Item::class, [
                "getId" => 1,
                 "getOrderItem" => $this->itemFactory(\Magento\Sales\Model\Order\Item::class, [
                     "getIsVirtual" => true,
                     "getOrigData" => 0
                 ]),
                "getBaseRowTotalInclTax" => 0,
                "getQty" => 0
            ]),
            $this->itemFactory(\Magento\Sales\Model\Order\Creditmemo\Item::class, [
                "getId" => 2,
                "getOrderItem" => $this->itemFactory(\Magento\Sales\Model\Order\Item::class, [
                    "getIsVirtual" => true,
                    "getOrigData" => 0
                ]),
                "getBaseRowTotalInclTax" => 0,
                "getQty" => 0
            ])
        ];
    }

    private function itemFactory($className, array $data): \Magento\Framework\Api\ExtensibleDataInterface
    {
        $item = $this->createMock($className);
        foreach ($data as $method => $returnValue) {
            $item->method($method)->willReturn($returnValue);
        }

        return $item;
    }
}
