<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order;

interface OrderItemInterface
{
    public function getAfterpayRefundedQty(): float;

    public function setAfterpayRefundedQty(float $qty): self;

    public function getAfterpayVoidedQty(): float;

    public function setAfterpayVoidedQty(float $qty): self;
}
