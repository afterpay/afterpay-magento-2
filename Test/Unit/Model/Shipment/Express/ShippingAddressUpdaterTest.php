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
        $this->region->method("loadByCode")->willReturnSelf();
        $this->region->method("loadByName")->willReturnSelf();
        $this->region->method("getId")->willReturn(1);
        $this->cartRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->shippingAddressUpdater = new \Afterpay\Afterpay\Model\Shipment\Express\ShippingAddressUpdater(
            $this->region,
            $this->cartRepository
        );
    }

    /**
     * @dataProvider dataToExceptionProvider
     * @param array $shippingAddress
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataToExceptionProvider')]
    public function testExceptionFillQuoteWithShippingAddress(array $shippingAddress)
    {
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->cartRepository->expects($this->never())->method("save");
        $this->expectException(\InvalidArgumentException::class);
        $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
    }

    public static function dataToExceptionProvider():array
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
            $exceptionData[] = [$cutFullShipping];
        }
        return $exceptionData;
    }

    /**
     * @dataProvider dataToVirtualQuote
     * @param array $shippingAddress
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataToVirtualQuote')]
    public function testVirtualQuote(array $shippingAddress)
    {
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->cartRepository->expects($this->never())->method("save");
        $quote->method("isVirtual")->willReturn(true);
        $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
    }

    public static function dataToVirtualQuote(): array
    {
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
            ]
        ];
    }

    /**
     * @dataProvider dataToTrival
     * @param array $shippingAddress
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataToTrival')]
    public function testTrivalFillQuoteWithShippingAddress(array $shippingAddress)
    {
        $quoteShippingAddress = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote
            ->method("getShippingAddress")
            ->willReturn($quoteShippingAddress);
        $this->cartRepository->expects($this->once())->method("save");
        $this->cartRepository->expects($this->once())->method("getActive")->willReturn($quote);
        $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
    }

    public static function dataToTrival(): array
    {
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
            ]
        ];
    }
}
