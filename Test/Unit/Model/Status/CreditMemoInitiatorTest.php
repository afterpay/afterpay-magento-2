<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Status;

class CreditMemoInitiatorTest extends \PHPUnit\Framework\TestCase
{
    private \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoInitiator $creditMemoInitiator;
    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditMemoFactory;
    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private $order;
    /**
     * @var \Magento\Sales\Model\Order\Creditmemo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditMemo;

    public function setUp(): void
    {
        $this->creditMemoFactory = $this->createMock(\Magento\Sales\Model\Order\CreditmemoFactory::class);
        $this->order = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->creditMemoInitiator = new \Afterpay\Afterpay\Model\Order\CreditMemo\CreditMemoInitiator(
            $this->creditMemoFactory
        );
        $this->creditMemo = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
    }

    /**
     * @dataProvider dataForInit
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataForInit')]
    public function testExecute(array $inputItems, array $qtysToRefund)
    {
        $items = array_map([$this, 'itemFactory'], $inputItems);
        $this->order->method('getItemsCollection')->willReturn($items);
        $this->creditMemoFactory->expects($this->once())->method("createByOrder")->with(
            $this->order,
            ["qtys" => $qtysToRefund]
        )->willReturn($this->creditMemo);
        $this->creditMemoInitiator->init($this->order);
    }

    public static function dataForInit():array
    {
        return [self::getNotOperableItemsTest(), self::getItemsMainTest()];
    }

    private static function getNotOperableItemsTest()
    {
        return [
            [
                [
                "getParentItem" => true,
                "getIsVirtual" => false,
                ],
                [
                    "getParentItem" => false,
                    "getIsVirtual" => true
                ],
                [
                    "getParentItem" => true,
                    "getIsVirtual" => true
                ]],
            []
        ];
    }

    private static function getItemsMainTest()
    {
        return [
            [
                [
                    "getId" => 1,
                    "getParentItem" => false,
                    "getIsVirtual" => false,
                    "getQtyOrdered" => 5,
                    "getQtyShipped" => 3,
                    "getQtyRefunded" => 2,
                ],
                [
                    "getId" => 2,
                    "getParentItem" => false,
                    "getIsVirtual" => false,
                    "getQtyOrdered" => 5,
                    "getQtyShipped" => 3,
                    "getQtyRefunded" => 1,
                ],
                [
                    "getId" => 3,
                    "getParentItem" => false,
                    "getIsVirtual" => false,
                    "getQtyOrdered" => 0,
                    "getQtyShipped" => 3,
                    "getQtyRefunded" => 1,
                ],
                [
                    "getId" => 4,
                    "getParentItem" => false,
                    "getIsVirtual" => false,
                    "getQtyOrdered" => 5,
                    "getQtyShipped" => 2,
                    "getQtyRefunded" => 1,
                ],
                [
                    "getId" => 5,
                    "getParentItem" => false,
                    "getIsVirtual" => false,
                    "getQtyOrdered" => 0,
                    "getQtyShipped" => 0,
                    "getQtyRefunded" => 0,
                ],
                [
                    "getId" => 6,
                    "getParentItem" => false,
                    "getIsVirtual" => false,
                    "getQtyOrdered" => 1000,
                    "getQtyShipped" => 300,
                    "getQtyRefunded" => 400,
                ]
            ],
            [
                2 => 1,
                4 => 2,
                6 => 300
            ]
        ];
    }

    private function itemFactory(array $data): \Magento\Sales\Model\Order\Item
    {
        $item = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        foreach ($data as $method => $returnValue) {
            $item->method($method)->willReturn($returnValue);
        }
        return $item;
    }
}
