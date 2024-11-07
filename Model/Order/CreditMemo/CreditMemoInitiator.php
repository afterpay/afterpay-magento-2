<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

class CreditMemoInitiator
{
    private $creditmemoFactory;

    public function __construct(
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
    }

    public function init(\Magento\Sales\Model\Order $order): \Magento\Sales\Model\Order\Creditmemo
    {
        $qtysToRefund = [];
        /** @var \Magento\Sales\Model\Order\Item $orderItem  */
        foreach ($order->getItemsCollection() as $orderItem) {
            if ($orderItem->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                /** @var \Magento\Sales\Model\Order\Item $childrenItem  */
                foreach ($orderItem->getChildrenItems() as $childrenItem) {
                    if (!$childrenItem->getIsVirtual()) {
                        $qtyShipped = $childrenItem->getQtyShipped();
                        $qtyOrdered = $childrenItem->getQtyOrdered();
                        $qtyRefunded = $childrenItem->getQtyRefunded();
                        $childItemLeftToShip = $qtyOrdered - ($qtyShipped + $qtyRefunded);
                        if ($childItemLeftToShip > 0) {
                            $qtysToRefund[$childrenItem->getId()] = $childItemLeftToShip;
                        }
                    }
                }
            }

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
