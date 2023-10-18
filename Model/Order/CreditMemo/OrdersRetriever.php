<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

use Afterpay\Afterpay\Api\Data\TokenInterface;
use Afterpay\Afterpay\Model\ResourceModel\Token\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class OrdersRetriever
{
    private $orderCollectionFactory;
    private $resourceConnection;
    private $tokensCollectionFactory;
    private $dateTime;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ResourceConnection     $resourceConnection,
        CollectionFactory      $tokensCollectionFactory,
        DateTime               $dateTime
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->tokensCollectionFactory = $tokensCollectionFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * @return Order[]
     */
    public function getAfterpayOrders(): array
    {
        $tokensCollection = $this->tokensCollectionFactory->create()
            ->addFieldToSelect(TokenInterface::ORDER_ID_FIELD)
            ->addFieldToFilter(TokenInterface::EXPIRATION_DATE_FIELD, ['notnull' => true])
            ->addFieldToFilter(
                TokenInterface::EXPIRATION_DATE_FIELD,
                [
                    'date' => true,
                    'from' => $this->dateTime->date('Y-m-d H:i:s', '-90 days'),
                    'to'   => $this->dateTime->date('Y-m-d H:i:s')
                ]
            );
        $ids = $tokensCollection->getColumnValues(TokenInterface::ORDER_ID_FIELD);

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter(
            OrderInterface::ENTITY_ID,
            ['in' => $ids]
        )->addFieldToFilter(
            OrderInterface::STATE,
            ['eq' => Order::STATE_PROCESSING]
        );
        $orderCollection = $this->joinAfterpayPaymentAdditionalInfo($orderCollection);

        return $orderCollection->getItems();
    }

    private function joinAfterpayPaymentAdditionalInfo(
        Collection $orderCollection
    ): Collection {
        $salesOrderPaymentTable = $this->resourceConnection->getTableName('sales_order_payment');
        $orderCollection->join(
            ['sop' => $salesOrderPaymentTable],
            'sop.parent_id = main_table.entity_id',
            OrderPaymentInterface::ADDITIONAL_INFORMATION
        );

        return $orderCollection;
    }
}
