<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Cron;

class OfflineCreditMemo
{
    private $statusChanger;

    public function __construct(
        \Afterpay\Afterpay\Model\Order\CreditMemo\StatusChanger $statusChanger
    ) {
        $this->statusChanger = $statusChanger;
    }

    public function execute(): void
    {
        $this->statusChanger->execute();
    }
}
