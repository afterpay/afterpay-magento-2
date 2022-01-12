<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Shipment\Express;

class ShippingAddressUpdaterTest extends \PHPUnit\Framework\TestCase
{
    private \Afterpay\Afterpay\Model\Shipment\Express\ShippingAddressUpdater $shippingAddressUpdater;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartRepository;
    /**
     * @var \Magento\Directory\Model\Region|\PHPUnit\Framework\MockObject\MockObject
     */
    private $region;

    protected function setUp(): void
    {
        $this->region = $this->createMock(\Magento\Directory\Model\Region::class);
        $this->region->expects($this->any())->method("loadByCode")->willReturnSelf();
        $this->cartRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->shippingAddressUpdater = new \Afterpay\Afterpay\Model\Shipment\Express\ShippingAddressUpdater(
            $this->region,
            $this->cartRepository
        );
    }

    /**
     * @dataProvider dataToExceptionProvider
     * @param array $shippingAddress
     * @param \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject $quote
     */
    public function testExceptionFillQuoteWithShippingAddress(array $shippingAddress, $quote)
    {
        $this->cartRepository->expects($this->never())->method("save");
        $this->expectException(\InvalidArgumentException::class);
        $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
    }

    public function dataToExceptionProvider():array
    {
        $exceptionData = [];
        $fullShippingAddress = [
            'name'=> 'some date',
            'address1'=> 'some date',
            'address2'=> 'some date',
            'countryCode'=> 'some date',
            'suburb'=> 'some date',
            'postcode'=> 'some date',
            'state'=> 'some date',
            'phoneNumber' => 'some date'
        ];
        foreach (array_keys($fullShippingAddress) as $shippingKey) {
            $cutFullShipping = $fullShippingAddress;
            unset($cutFullShipping[$shippingKey]);
            $exceptionData[] = [$cutFullShipping, $this->createMock(\Magento\Quote\Model\Quote::class)];
        }
        return $exceptionData;
    }

    /**
     * @dataProvider dataToVirtualQuote
     * @param array $shippingAddress
     * @param \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject $quote
     */
    public function testVirtualQuote(array $shippingAddress, $quote)
    {
        $this->cartRepository->expects($this->never())->method("save");
        $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
    }

    public function dataToVirtualQuote(): array
    {
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->any())->method("isVirtual")->willReturn(true);
        return [
            [
            [
                'name'=> 'some date',
                'address1'=> 'some date',
                'address2'=> 'some date',
                'countryCode'=> 'some date',
                'suburb'=> 'some date',
                'postcode'=> 'some date',
                'state'=> 'some date',
                'phoneNumber' => 'some date'
            ],
                $quote
            ]
        ];
    }

    /**
     * @dataProvider dataToTrival
     * @param array $shippingAddress
     * @param \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject $quote
     */
    public function testTrivalFillQuoteWithShippingAddress(array $shippingAddress, $quote)
    {
        $this->cartRepository->expects($this->once())->method("save");
        $this->cartRepository->expects($this->once())->method("getActive")->willReturn($quote);
        $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
    }

    public function dataToTrival(): array
    {
        $shippingAddress = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->any())
            ->method("getShippingAddress")
            ->willReturn($shippingAddress);
        return [
            [
                [
                    'name'=> 'some date',
                    'address1'=> 'some date',
                    'address2'=> 'some date',
                    'countryCode'=> 'some date',
                    'suburb'=> 'some date',
                    'postcode'=> 'some date',
                    'state'=> 'some date',
                    'phoneNumber' => 'some date'
                ],
                $quote
            ]
        ];
    }
}
