<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Placement\Service;

use Afterpay\Afterpay\Model\Placement\Client;
use Afterpay\Afterpay\Model\Placement\Service\GetPlacementData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetPlacementDataTest extends TestCase
{
    private GetPlacementData $getPlacementData;

    /** @var Client|MockObject */
    private $clientMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->getPlacementData = new GetPlacementData($this->clientMock);
    }

    public function testExecuteReturnsOnsitePlacements(): void
    {
        $mpid = 'test-mpid-123';
        $expectedPlacements = [
            ['pageType' => 'product', 'placementId' => 'pdp-123'],
            ['pageType' => 'cart', 'placementId' => 'cart-456']
        ];

        $apiResponse = [
            'mcrResponse' => [
                'data' => [
                    'config' => [
                        'config' => [
                            'onsitePlacements' => [
                                'details' => [
                                    'onsitePlacements' => $expectedPlacements
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame($expectedPlacements, $result);
    }

    public function testExecuteReturnsEmptyArrayOnEmptyResponse(): void
    {
        $mpid = 'test-mpid-123';

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn([]);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayOnMissingMcrResponse(): void
    {
        $mpid = 'test-mpid-123';

        $apiResponse = [
            'someOtherKey' => 'someValue'
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayOnMissingDataKey(): void
    {
        $mpid = 'test-mpid-123';

        $apiResponse = [
            'mcrResponse' => [
                'otherKey' => 'value'
            ]
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayOnMissingConfigKey(): void
    {
        $mpid = 'test-mpid-123';

        $apiResponse = [
            'mcrResponse' => [
                'data' => [
                    'otherKey' => 'value'
                ]
            ]
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayOnMissingOnsitePlacements(): void
    {
        $mpid = 'test-mpid-123';

        $apiResponse = [
            'mcrResponse' => [
                'data' => [
                    'config' => [
                        'config' => [
                            'someOtherConfig' => 'value'
                        ]
                    ]
                ]
            ]
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayOnMissingOnsitePlacementsDetails(): void
    {
        $mpid = 'test-mpid-123';

        $apiResponse = [
            'mcrResponse' => [
                'data' => [
                    'config' => [
                        'config' => [
                            'onsitePlacements' => [
                                'otherKey' => 'value'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame([], $result);
    }

    /**
     * @dataProvider placementsDataProvider
     */
    public function testExecuteWithVariousPlacements(array $placements): void
    {
        $mpid = 'test-mpid-123';

        $apiResponse = [
            'mcrResponse' => [
                'data' => [
                    'config' => [
                        'config' => [
                            'onsitePlacements' => [
                                'details' => [
                                    'onsitePlacements' => $placements
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->clientMock->expects($this->once())
            ->method('execute')
            ->with($mpid)
            ->willReturn($apiResponse);

        $result = $this->getPlacementData->execute($mpid);

        $this->assertSame($placements, $result);
    }

    public function placementsDataProvider(): array
    {
        return [
            'single product placement' => [
                [['pageType' => 'product', 'placementId' => 'pdp-only']]
            ],
            'single cart placement' => [
                [['pageType' => 'cart', 'placementId' => 'cart-only']]
            ],
            'multiple placements' => [
                [
                    ['pageType' => 'product', 'placementId' => 'pdp-123'],
                    ['pageType' => 'cart', 'placementId' => 'cart-456'],
                    ['pageType' => 'checkout', 'placementId' => 'checkout-789']
                ]
            ],
            'empty placements array' => [
                []
            ]
        ];
    }
}

