<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Gateway\Http\Client;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    private \Afterpay\Afterpay\Gateway\Http\Client\Client $client;

    /** @var \Magento\Framework\HTTP\ClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->getMockForAbstractClass(\Magento\Framework\HTTP\ClientInterface::class);
        $serializer = new \Magento\Framework\Serialize\Serializer\Json();
        $loggerMock = $this->getMockForAbstractClass(\Psr\Log\LoggerInterface::class);
        $debugLoggerMock = $this->createMock(\Magento\Payment\Model\Method\Logger::class);
        $requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->client = new \Afterpay\Afterpay\Gateway\Http\Client\Client(
            $this->httpClientMock,
            $serializer,
            $loggerMock,
            $debugLoggerMock,
            $requestMock
        );

        $this->httpClientMock->expects($this->any())
            ->method('getBody')
            ->willReturn($serializer->serialize(['test_result' => true]));
    }

    /**
     * @param string|array $bodyFromTransfer
     * @param string|array $expectedBodyForHttpClient
     *
     * @dataProvider bodyProvider
     */
    public function testHttpClientBody($bodyFromTransfer, $expectedBodyForHttpClient): void
    {
        $testUri = 'https://test.test';

        $transferMock = $this->getMockForAbstractClass(\Magento\Payment\Gateway\Http\TransferInterface::class);
        $transferMock->method('getMethod')
            ->willReturn(\Afterpay\Afterpay\Gateway\Http\TransferFactory::METHOD_POST);
        $transferMock->method('getUri')
            ->willReturn($testUri);

        $transferMock->method('getBody')
            ->willReturn($bodyFromTransfer);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with($testUri, $expectedBodyForHttpClient);

        $this->client->placeRequest($transferMock);
    }

    /**
     * @dataProvider uriProvider
     */
    public function testHttpClientUri(string $uri): void
    {
        $transferMock = $this->getMockForAbstractClass(\Magento\Payment\Gateway\Http\TransferInterface::class);

        $transferMock->method('getUri')
            ->willReturn($uri);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($uri);

        $this->client->placeRequest($transferMock);
    }

    public function testHttpClientPostMethod(): void
    {
        $transferMock = $this->getMockForAbstractClass(\Magento\Payment\Gateway\Http\TransferInterface::class);

        $transferMock->method('getMethod')
            ->willReturn(\Afterpay\Afterpay\Gateway\Http\TransferFactory::METHOD_POST);
        $this->httpClientMock->expects($this->once())
            ->method('post');

        $this->client->placeRequest($transferMock);
    }

    public function testHttpClientGetMethod(): void
    {
        $transferMock = $this->getMockForAbstractClass(\Magento\Payment\Gateway\Http\TransferInterface::class);

        $transferMock->method('getMethod')
            ->willReturn(\Afterpay\Afterpay\Gateway\Http\TransferFactory::METHOD_GET);
        $this->httpClientMock->expects($this->once())
            ->method('get');

        $this->client->placeRequest($transferMock);
    }

    public function bodyProvider(): array
    {
        return [
            [[],[]],
            ['', []],
            ['{"json":true}', '{"json":true}'],
            [['is_json' => true], json_encode(['is_json' => true])],
            [null, []],
            [123, '123']
        ];
    }

    public function uriProvider(): array
    {
        return [
            ['https://test.test'],
            ['https://magento.com'],
            ['http://test.test'],
            ['http://magento.com'],
        ];
    }
}
