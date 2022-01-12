<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order;

class OrderItem implements OrderItemInterface
{
    private $refundedQty = 0;
    private $voidedQty = 0;

    public function getAfterpayRefundedQty(): float
    {
        return $this->refundedQty;
    }

    public function setAfterpayRefundedQty(float $qty): OrderItemInterface
    {
        $this->refundedQty = $qty;
        return $this;
    }

    public function getAfterpayVoidedQty(): float
    {
        return $this->voidedQty;
    }

    public function setAfterpayVoidedQty(float $qty): OrderItemInterface
    {
        $this->voidedQty = $qty;
        return $this;
    }
}
