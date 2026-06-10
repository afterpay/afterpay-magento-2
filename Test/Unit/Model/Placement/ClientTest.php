<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\Placement;

use Afterpay\Afterpay\Model\Placement\Client;
use Afterpay\Afterpay\Model\Url\UrlBuilder;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Model\Method\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{
    private Client $client;

    /** @var ClientInterface|MockObject */
    private $httpClientMock;

    /** @var SerializerInterface|MockObject */
    private $serializerMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var Logger|MockObject */
    private $debugLoggerMock;

    /** @var UrlBuilder|MockObject */
    private $urlBuilderMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->getMockForAbstractClass(ClientInterface::class);
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->debugLoggerMock = $this->createMock(Logger::class);
        $this->urlBuilderMock = $this->createMock(UrlBuilder::class);

        $this->client = new Client(
            $this->httpClientMock,
            $this->serializerMock,
            $this->loggerMock,
            $this->debugLoggerMock,
            $this->urlBuilderMock
        );
    }

    public function testExecuteSuccessfulApiCall(): void
    {
        $mpid = 'test-mpid-123';
        $actionUrl = 'https://placement-api.afterpay.com/api/data/test-mpid-123';
        $responseBody = '{"mcrResponse":{"data":{"config":{"config":{"test":"value"}}}}}';
        $expectedResponse = ['mcrResponse' => ['data' => ['config' => ['config' => ['test' => 'value']]]]];

        $this->urlBuilderMock->expects($this->once())
            ->method('build')
            ->with(UrlBuilder::TYPE_PLACEMENT_API, $mpid)
            ->willReturn($actionUrl);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($actionUrl);

        $this->httpClientMock->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($responseBody);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($responseBody)
            ->willReturn($expectedResponse);

        $this->debugLoggerMock->expects($this->once())
            ->method('debug')
            ->with([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'response' => $expectedResponse
            ]);

        $result = $this->client->execute($mpid);

        $this->assertSame($expectedResponse, $result);
    }

    public function testExecuteReturnsEmptyArrayOnEmptyResponse(): void
    {
        $mpid = 'test-mpid-123';
        $actionUrl = 'https://placement-api.afterpay.com/api/data/test-mpid-123';

        $this->urlBuilderMock->expects($this->once())
            ->method('build')
            ->with(UrlBuilder::TYPE_PLACEMENT_API, $mpid)
            ->willReturn($actionUrl);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($actionUrl);

        $this->httpClientMock->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn('');

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $this->debugLoggerMock->expects($this->once())
            ->method('debug')
            ->with([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'response' => []
            ]);

        $result = $this->client->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayOnErrorResponse(): void
    {
        $mpid = 'test-mpid-123';
        $actionUrl = 'https://placement-api.afterpay.com/api/data/test-mpid-123';
        $responseBody = 'error code: 500 - Internal Server Error';

        $this->urlBuilderMock->expects($this->once())
            ->method('build')
            ->with(UrlBuilder::TYPE_PLACEMENT_API, $mpid)
            ->willReturn($actionUrl);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($actionUrl);

        $this->httpClientMock->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($responseBody);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with("Afterpay: Error fetching placement data for mpid: {$mpid}");

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $this->debugLoggerMock->expects($this->once())
            ->method('debug')
            ->with([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'response' => []
            ]);

        $result = $this->client->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteHandlesExceptionDuringApiCall(): void
    {
        $mpid = 'test-mpid-123';
        $actionUrl = 'https://placement-api.afterpay.com/api/data/test-mpid-123';
        $exceptionMessage = 'Connection timeout';

        $this->urlBuilderMock->expects($this->once())
            ->method('build')
            ->with(UrlBuilder::TYPE_PLACEMENT_API, $mpid)
            ->willReturn($actionUrl);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($actionUrl)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with(
                $this->stringContains("Afterpay: Error fetching placement data for mpid: {$mpid}. {$exceptionMessage}"),
                $this->anything()
            );

        $this->debugLoggerMock->expects($this->once())
            ->method('debug')
            ->with([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'response' => []
            ]);

        $result = $this->client->execute($mpid);

        $this->assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayWhenUnserializedBodyIsNotArray(): void
    {
        $mpid = 'test-mpid-123';
        $actionUrl = 'https://placement-api.afterpay.com/api/data/test-mpid-123';
        $responseBody = '"just a string"';

        $this->urlBuilderMock->expects($this->once())
            ->method('build')
            ->with(UrlBuilder::TYPE_PLACEMENT_API, $mpid)
            ->willReturn($actionUrl);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($actionUrl);

        $this->httpClientMock->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($responseBody);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($responseBody)
            ->willReturn('just a string');

        $this->debugLoggerMock->expects($this->once())
            ->method('debug')
            ->with([
                'merchant_public_id' => $mpid,
                'action_url' => $actionUrl,
                'response' => []
            ]);

        $result = $this->client->execute($mpid);

        $this->assertSame([], $result);
    }
}

