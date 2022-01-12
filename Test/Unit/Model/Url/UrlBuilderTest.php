<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Url;

class UrlBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider argumentsReplacementDataProvider
     */
    public function testPathArgumentsReplacement(string $path, array $args, string $expectedPath)
    {
        $testUrl = 'https://test.test/';
        $testType = \Afterpay\Afterpay\Model\Url\UrlBuilder::TYPE_API;

        $urlFactoryMock = $this->createMock(\Afterpay\Afterpay\Model\Url\UrlBuilder\UrlFactory::class);
        $urlFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($testUrl);

        $urlBuilder = new \Afterpay\Afterpay\Model\Url\UrlBuilder($urlFactoryMock);
        $url = $urlBuilder->build($testType, $path, $args);

        static::assertSame($testUrl . $expectedPath, $url);
    }

    public function argumentsReplacementDataProvider(): array
    {
        return [
            ['v2/payments/{orderId}/capture', ['orderId' => 12345], 'v2/payments/12345/capture'],
            ['v2/payments/auth', [], 'v2/payments/auth'],
            ['v2/payments/capture', ['unExistedArg' => 999], 'v2/payments/capture']
        ];
    }
}
