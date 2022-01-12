<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

class CreditMemoInitiator
{
    private \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory;

    public function __construct(
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
    }

    public function init(\Magento\Sales\Model\Order $order): \Magento\Sales\Model\Order\Creditmemo
    {
        $qtysToRefund = [];
        foreach ($order->getItemsCollection() as $orderItem) {
            /** @var $orderItem \Magento\Sales\Model\Order\Item  */
            if (!$orderItem->getParentItem() && !$orderItem->getIsVirtual()) {
                $qtyShipped = $orderItem->getQtyShipped();
                $qtyOrdered = $orderItem->getQtyOrdered();
                $qtyRefunded = $orderItem->getQtyRefunded();
                $itemLeftToShip = $qtyOrdered - ($qtyShipped + $qtyRefunded);
                if ($itemLeftToShip > 0) {
                    $qtysToRefund[$orderItem->getId()] = $itemLeftToShip;
                }
            }
        }
        return $this->creditmemoFactory->createByOrder($order, ['qtys' => $qtysToRefund]);
    }
}
