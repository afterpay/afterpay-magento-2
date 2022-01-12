<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Gateway\Command;

class CommandPoolProxyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider paymentFlowCommandDataProvider
     */
    public function testCaptureCommand(string $paymentFlow, string $expectedCommand): void
    {
        $afterpayConfigMock = $this->getMockBuilder(\Afterpay\Afterpay\Model\Config::class)
            ->onlyMethods(['getPaymentFlow'])
            ->disableOriginalConstructor()
            ->getMock();

        $commandPoolFactory = $this->getMockBuilder('Magento\Payment\Gateway\Command\CommandPoolFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $commandPoolProxy = new \Afterpay\Afterpay\Gateway\Command\CommandPoolProxy(
            $commandPoolFactory,
            $afterpayConfigMock,
            [
                'capture_immediate' => 'Afterpay\Afterpay\Gateway\Command\CaptureCommand',
                'auth_deferred' => 'Afterpay\Afterpay\Gateway\Command\AuthCommand',
            ]
        );

        $afterpayConfigMock->expects($this->atLeastOnce())
            ->method('getPaymentFlow')
            ->willReturn($paymentFlow);

        $commandPoolInterfaceStub = $this->getMockForAbstractClass(
            \Magento\Payment\Gateway\Command\CommandPoolInterface::class
        );
        $commandPoolInterfaceStub->method('get')
            ->willReturn($this->createMock(\Magento\Payment\Gateway\CommandInterface::class));

        $commandPoolFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with([
                'commands' => [
                    'capture' => $expectedCommand
                ]
            ])->willReturn($commandPoolInterfaceStub);

        $commandPoolProxy->get('capture');
    }

    public function paymentFlowCommandDataProvider(): array
    {
        return [
            [
                \Afterpay\Afterpay\Model\Config\Source\PaymentFlow::IMMEDIATE,
                'Afterpay\Afterpay\Gateway\Command\CaptureCommand'
            ],
            [
                \Afterpay\Afterpay\Model\Config\Source\PaymentFlow::DEFERRED,
                'Afterpay\Afterpay\Gateway\Command\AuthCommand'
            ],
            [
                'UNDEFINED PAYMENT FLOW',
                'Afterpay\Afterpay\Gateway\Command\CaptureCommand'
            ]
        ];
    }
}
