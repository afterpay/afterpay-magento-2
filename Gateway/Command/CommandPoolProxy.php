<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Command;

class CommandPoolProxy implements \Magento\Payment\Gateway\Command\CommandPoolInterface
{
    protected $commandPool = null;

    private $commandPoolFactory;
    private $config;
    private $commands;

    public function __construct(
        \Magento\Payment\Gateway\Command\CommandPoolFactory $commandPoolFactory,
        \Afterpay\Afterpay\Model\Config $config,
        array $commands = []
    ) {
        $this->commandPoolFactory = $commandPoolFactory;
        $this->config = $config;
        $this->commands = $commands;
    }

    protected function getCommandPool(): \Magento\Payment\Gateway\Command\CommandPoolInterface
    {
        if ($this->commandPool === null) {
            $paymentFlow = $this->config->getPaymentFlow();

            $this->commands['capture'] = $paymentFlow == \Afterpay\Afterpay\Model\Config\Source\PaymentFlow::DEFERRED ?
                $this->commands['auth_deferred'] :
                $this->commands['capture_immediate'];

            unset($this->commands['capture_immediate']);
            unset($this->commands['auth_deferred']);

            $this->commandPool = $this->commandPoolFactory->create(['commands' => $this->commands]);
        }
        return $this->commandPool;
    }

    public function get($commandCode): \Magento\Payment\Gateway\CommandInterface
    {
        return $this->getCommandPool()->get($commandCode);
    }
}
