<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

class StatusChanger
{
    private $ordersRetriever;
    private $creditMemoProcessor;
    private $logger;

    public function __construct(
        OrdersRetriever $ordersRetriever,
        CreditMemoProcessor $creditMemoProcessor,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->ordersRetriever = $ordersRetriever;
        $this->creditMemoProcessor = $creditMemoProcessor;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $orders = $this->ordersRetriever->getAfterpayOrders();
        foreach ($orders as $order) {
            try {
                $this->creditMemoProcessor->processOrder($order);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
