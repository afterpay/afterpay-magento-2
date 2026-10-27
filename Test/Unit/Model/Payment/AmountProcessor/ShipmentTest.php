<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Payment\AmountProcessor;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Model\Payment\AmountProcessor\Shipment as ShipmentAmountProcessor;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Weee\Block\Item\Price\Renderer;

class ShipmentTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_BASE_TOTAL = 50.0;
    private const ITEM_TOTAL = 75.0;

    private ShipmentAmountProcessor $shipmentAmountProcessor;

    public function setUp(): void
    {
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('getBaseTotalAmount')->willReturn(self::ITEM_BASE_TOTAL);
        $renderer->method('getTotalAmount')->willReturn(self::ITEM_TOTAL);

        $this->shipmentAmountProcessor = new ShipmentAmountProcessor($renderer);
    }

    /**
     * @dataProvider shippingDataProvider
     */
    public function testCaptureAmountMatchesAuthorizedShipping(
        array $orderData,
        bool $isCBTCurrency,
        int $previousShipments,
        float $expectedAmount
    ): void {
        $payment = $this->createPayment($orderData, $isCBTCurrency, $previousShipments);

        $this->assertEqualsWithDelta(
            $expectedAmount,
            $this->shipmentAmountProcessor->process([$this->createShipmentItem()], $payment),
            0.0001
        );
    }

    public static function shippingDataProvider(): array
    {
        return [
            'no shipping discount' => [
                self::singleCurrency(['shipping_amount' => '10.0000', 'shipping_incl_tax' => '10.0000']),
                false, 0, 60.0
            ],
            '100% shipping discount from a cart price rule' => [
                self::singleCurrency([
                    'shipping_amount' => '10.0000',
                    'shipping_incl_tax' => '10.0000',
                    'shipping_discount_amount' => '10.0000'
                ]),
                false, 0, 50.0
            ],
            // 10% tax applied after discount: tax is charged on 5.00 only, while shipping_incl_tax is 11.00
            'partial shipping discount, tax after discount' => [
                self::singleCurrency([
                    'shipping_amount' => '10.0000',
                    'shipping_incl_tax' => '11.0000',
                    'shipping_discount_amount' => '5.0000',
                    'shipping_tax_amount' => '0.5000'
                ]),
                false, 0, 55.5
            ],
            // Shipping price 11.00 incl. 10% tax, 50% discount on the incl. tax price: customer pays 5.50
            'shipping discount on prices including tax' => [
                self::singleCurrency([
                    'shipping_amount' => '10.0000',
                    'shipping_incl_tax' => '11.0000',
                    'shipping_discount_amount' => '5.5000',
                    'shipping_tax_amount' => '0.5000',
                    'shipping_discount_tax_compensation_amount' => '0.5000'
                ]),
                false, 0, 55.5
            ],
            'base currency is used for non-CBT orders' => [
                [
                    'base_shipping_amount' => '10.0000',
                    'base_shipping_incl_tax' => '10.0000',
                    'base_shipping_discount_amount' => '4.0000',
                    'shipping_amount' => '15.0000',
                    'shipping_incl_tax' => '15.0000',
                    'shipping_discount_amount' => '6.0000'
                ],
                false, 0, 56.0
            ],
            'order currency is used for CBT orders' => [
                [
                    'base_shipping_amount' => '10.0000',
                    'base_shipping_incl_tax' => '10.0000',
                    'base_shipping_discount_amount' => '4.0000',
                    'shipping_amount' => '15.0000',
                    'shipping_incl_tax' => '15.0000',
                    'shipping_discount_amount' => '6.0000'
                ],
                true, 0, 84.0
            ],
            'shipping is captured with the first shipment only' => [
                self::singleCurrency([
                    'shipping_amount' => '10.0000',
                    'shipping_incl_tax' => '10.0000',
                    'shipping_discount_amount' => '4.0000'
                ]),
                false, 1, 50.0
            ],
            // Credit memo refunds the shipping discount pro rata: half the shipping refunded => half the discount
            'shipping partially refunded before shipment' => [
                self::singleCurrency([
                    'shipping_amount' => '10.0000',
                    'shipping_incl_tax' => '11.0000',
                    'shipping_discount_amount' => '4.0000',
                    'shipping_tax_amount' => '0.6000',
                    'shipping_refunded' => '5.0000',
                    'shipping_tax_refunded' => '0.3000'
                ]),
                false, 0, 53.3
            ],
            'shipping fully refunded before shipment' => [
                self::singleCurrency([
                    'shipping_amount' => '10.0000',
                    'shipping_incl_tax' => '11.0000',
                    'shipping_discount_amount' => '4.0000',
                    'shipping_tax_amount' => '0.6000',
                    'shipping_refunded' => '10.0000',
                    'shipping_tax_refunded' => '0.6000'
                ]),
                false, 0, 50.0
            ],
        ];
    }

    /**
     * Order where base and order currency are the same: sets every value in both currencies
     */
    private static function singleCurrency(array $orderData): array
    {
        $result = $orderData;
        foreach ($orderData as $key => $value) {
            $baseKey = $key === 'shipping_discount_tax_compensation_amount'
                ? 'base_shipping_discount_tax_compensation_amnt'
                : 'base_' . $key;
            $result[$baseKey] = $value;
        }
        return $result;
    }

    private function createPayment(
        array $orderData,
        bool $isCBTCurrency,
        int $previousShipments
    ): Order\Payment {
        $payment = $this->createMock(Order\Payment::class);
        $payment->method('getAdditionalInformation')->willReturnCallback(
            static fn ($key = null) => $key === CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY ? $isCBTCurrency : null
        );

        $shipmentCollection = $this->createMock(ShipmentCollection::class);
        $shipmentCollection->method('count')->willReturn($previousShipments);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShipmentsCollection', 'getPayment'])
            ->getMock();
        $order->setData($orderData);
        $order->method('getShipmentsCollection')->willReturn($shipmentCollection);
        $order->method('getPayment')->willReturn($payment);

        $payment->method('getOrder')->willReturn($order);

        return $payment;
    }

    private function createShipmentItem(): Order\Shipment\Item
    {
        $orderItem = $this->createMock(Order\Item::class);
        $orderItem->method('getParentItem')->willReturn(null);
        $orderItem->method('getQtyOrdered')->willReturn(1.0);

        $shipmentItem = $this->createMock(Order\Shipment\Item::class);
        $shipmentItem->method('getOrderItem')->willReturn($orderItem);
        $shipmentItem->method('getQty')->willReturn(1.0);

        return $shipmentItem;
    }
}
