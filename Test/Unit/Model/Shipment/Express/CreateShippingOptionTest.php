<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Shipment\Express;

class CreateShippingOptionTest extends \PHPUnit\Framework\TestCase
{
    private $createShippingOption;
    /**
     * @var \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalsInformationFactory;
    /**
     * @var \Magento\Checkout\Api\TotalsInformationManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalsInformationManagement;
    /**
     * @var \Afterpay\Afterpay\Model\Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;
    /**
     * @var \Magento\Checkout\Api\Data\TotalsInformationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $totalsInformation;
    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->config = $this->createMock(\Afterpay\Afterpay\Model\Config::class);
        $this->totalsInformationManagement = $this->createMock(
            \Magento\Checkout\Api\TotalsInformationManagementInterface::class
        );
        $this->totalsInformationFactory = $this->createMock(
            \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory::class
        );
        $this->totalsInformation = $this->createMock(
            \Magento\Checkout\Api\Data\TotalsInformationInterface::class
        );
        $this->totalsInformation->method("setAddress")->willReturnSelf();
        $this->totalsInformation->method("setShippingCarrierCode")->willReturnSelf();
        $this->totalsInformation->method("setShippingMethodCode")->willReturnSelf();
        $this->totalsInformationFactory->method("create")->willReturn($this->totalsInformation);
        $this->quote =  $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quote->method("getShippingAddress")
            ->willReturn(
                $this->createMock(\Magento\Quote\Api\Data\AddressInterface::class)
            );
        $this->createShippingOption = new \Afterpay\Afterpay\Model\Shipment\Express\CreateShippingOption(
            $this->config,
            $this->totalsInformationManagement,
            $this->totalsInformationFactory
        );
    }

    /**
     * @dataProvider resultTestDataProvider
     * @dataProvider resultNullAmountTestDataProvider
     * @dataProvider orderLimitDataProvider
     * @param \Magento\Quote\Api\Data\TotalsInterface|\PHPUnit\Framework\MockObject\MockObject $calculatedTotals
     * @param \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject $quote
     * @param \Magento\Quote\Api\Data\ShippingMethodInterface|\PHPUnit\Framework\MockObject\MockObject $shippingMethod
     */
    public function testOrderLimit(
        ?string $minOrderLimit,
        ?string $maxOrderLimit,
        $calculatedTotals,
        $shippingMethod,
        ?array $result
    ) {
        $this->config->expects($this->once())->method("getMinOrderTotal")
            ->willReturn($minOrderLimit);
        $this->config->expects($this->atMost(1))->method("getMaxOrderTotal")
            ->willReturn($maxOrderLimit);
        $this->totalsInformationManagement->expects($this->once())
            ->method("calculate")
            ->willReturn($calculatedTotals);
        $this->assertEquals($result, $this->createShippingOption->create($this->quote, $shippingMethod));
    }

    public function orderLimitDataProvider()
    {
        $carrierCode = 'carrierCode';
        $carrierMethod = 'carrierMethod';
        $carrierTitle = 'carrierTitle';
        $baseGrandTotal = 10000;
        $baseShippingAmount = 20;
        $baseTaxAmount = 10;
        return [
            [
                "0",
                "1000",
                $this->createCalculatedTotals($baseGrandTotal, $baseShippingAmount, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                null,
            ],
            [
                "10000",
                "1000",
                $this->createCalculatedTotals($baseGrandTotal, $baseShippingAmount, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                null,
            ],
            [
                null,
                "1000",
                $this->createCalculatedTotals($baseGrandTotal, $baseShippingAmount, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                null,
            ],
            [
                "0",
                null,
                $this->createCalculatedTotals($baseGrandTotal, $baseShippingAmount, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                null,
            ],
            [
                null,
                null,
                $this->createCalculatedTotals($baseGrandTotal, $baseShippingAmount, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                null,
            ]
        ];
    }

    public function resultNullAmountTestDataProvider()
    {
        $carrierCode = 'carrierCode';
        $carrierMethod = 'carrierMethod';
        $carrierTitle = 'carrierTitle';
        $baseGrandTotal = 10000;
        $baseShippingAmount = 20;
        $baseTaxAmount = 10;
        return [
            [
                "0",
                "100000",
                $this->createCalculatedTotals(null, $baseShippingAmount, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                null
            ],
            [
                "0",
                "100000",
                $this->createCalculatedTotals($baseGrandTotal, null, $baseTaxAmount),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                [
                    'id' => $carrierCode . "_" . $carrierMethod,
                    'name' => $carrierTitle,
                    'description' => $carrierTitle,
                    'shippingAmount' => [
                        'amount' => "0.00",
                        'currency' => null
                    ],
                    'taxAmount' => [
                        'amount' => "10.00",
                        'currency' => null
                    ],
                    'orderAmount' => [
                        'amount' => "10000.00",
                        'currency' => null
                    ]
                ],
            ],
            [
                "0",
                "100000",
                $this->createCalculatedTotals($baseGrandTotal, $baseShippingAmount, null),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                [
                    'id' => $carrierCode . "_" . $carrierMethod,
                    'name' => $carrierTitle,
                    'description' => $carrierTitle,
                    'shippingAmount' => [
                        'amount' => "20.00",
                        'currency' => null
                    ],
                    'taxAmount' => [
                        'amount' => "0.00",
                        'currency' => null
                    ],
                    'orderAmount' => [
                        'amount' => "10000.00",
                        'currency' => null
                    ]
                ],
            ],
            [
                "0",
                "100000",
                $this->createCalculatedTotals($baseGrandTotal, null, null),
                $this->createShippingMethod($carrierCode, $carrierMethod, $carrierTitle),
                [
                    'id' => $carrierCode . "_" . $carrierMethod,
                    'name' => $carrierTitle,
                    'description' => $carrierTitle,
                    'shippingAmount' => [
                        'amount' => "0.00",
                        'currency' => null
                    ],
                    'taxAmount' => [
                        'amount' => "0.00",
                        'currency' => null
                    ],
                    'orderAmount' => [
                        'amount' => "10000.00",
                        'currency' => null
                    ]
                ],
            ],
        ];
    }

    public function resultTestDataProvider()
    {
        return [
            [
                "123",
                "1234",
                $this->createCalculatedTotals(1233.11, 12.13, 11.15),
                $this->createShippingMethod("carrierCode1", "carrierMethod1", "carrierTitle1"),
                [
                    'id' => "carrierCode1" . "_" . "carrierMethod1",
                    'name' => "carrierTitle1",
                    'description' => "carrierTitle1",
                    'shippingAmount' => [
                        'amount' => "12.13",
                        'currency' => null
                    ],
                    'taxAmount' => [
                        'amount' => "11.15",
                        'currency' => null
                    ],
                    'orderAmount' => [
                        'amount' => "1233.11",
                        'currency' => null
                    ]
                ],
            ],
            [
                "22222",
                "55555",
                $this->createCalculatedTotals(33333.33, 324.13, 534.234),
                $this->createShippingMethod("carrierCode2", "carrierMethod2", "carrierTitle2"),
                [
                    'id' => "carrierCode2" . "_" . "carrierMethod2",
                    'name' => "carrierTitle2",
                    'description' => "carrierTitle2",
                    'shippingAmount' => [
                        'amount' => "324.13",
                        'currency' => null
                    ],
                    'taxAmount' => [
                        'amount' => "534.23",
                        'currency' => null
                    ],
                    'orderAmount' => [
                        'amount' => "33333.33",
                        'currency' => null
                    ]
                ],
            ]
        ];
    }

    private function createCalculatedTotals(
        ?float $baseGrandTotal = 0,
        ?float $baseShippingAmount = 0,
        ?float $baseTaxAmount = 0
    ) {
        $calculatedTotals = $this->createMock(\Magento\Quote\Api\Data\TotalsInterface::class);
        $calculatedTotals->expects($this->any())->method("getBaseGrandTotal")->willReturn($baseGrandTotal);
        $calculatedTotals->expects($this->any())->method("getBaseTaxAmount")->willReturn($baseTaxAmount);
        $calculatedTotals->expects($this->any())->method("getBaseShippingAmount")->willReturn($baseShippingAmount);
        return $calculatedTotals;
    }

    private function createShippingMethod(string $carrierCode, string $methodCode, string $carrierTitle)
    {
        $shippingMethod = $this->createMock(\Magento\Quote\Api\Data\ShippingMethodInterface::class);
        $shippingMethod->method("getCarrierCode")->willReturn($carrierCode);
        $shippingMethod->method("getMethodCode")->willReturn($methodCode);
        $shippingMethod->method("getCarrierTitle")->willReturn($carrierTitle);
        return $shippingMethod;
    }
}
