<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\CreditMemo;

use Afterpay\Afterpay\Api\Data\TokenInterface;
use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\ResourceModel\Token\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;

class OrdersRetriever
{
    private $orderCollectionFactory;
    private $resourceConnection;
    private $tokensCollectionFactory;
    private $dateTime;
    private $serializer;
    private $logger;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ResourceConnection     $resourceConnection,
        CollectionFactory      $tokensCollectionFactory,
        DateTime               $dateTime,
        Json                   $serializer,
        LoggerInterface        $logger
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->tokensCollectionFactory = $tokensCollectionFactory;
        $this->dateTime = $dateTime;
        $this->serializer = $serializer;
        $this->logger = $logger;
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

        return $this->getItemsWithAdditionalInfo($orderCollection->getItems());
    }

    /**
     * @param Order[] $items
     *
     * @return array
     */
    private function getItemsWithAdditionalInfo(array $items): array
    {
        $itemsWithJsonAdditionalInfo = [];
        foreach ($items as $item) {
            $additionalInformation = $item->getData(
                OrderPaymentInterface::ADDITIONAL_INFORMATION
            );
            try {
                $unserializedInfo = !empty($additionalInformation) ? $this->serializer->unserialize($additionalInformation) : null;
                if (!is_array($unserializedInfo)) {
                    continue;
                }

                $item->setData(OrderPaymentInterface::ADDITIONAL_INFORMATION, $unserializedInfo);
                if (isset(
                    $unserializedInfo[AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE],
                    $unserializedInfo[AdditionalInformationInterface::AFTERPAY_OPEN_TO_CAPTURE_AMOUNT],
                    $unserializedInfo[AdditionalInformationInterface::AFTERPAY_AUTH_EXPIRY_DATE]
                )) {
                    $itemsWithJsonAdditionalInfo[] = $item;
                }
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning('Error during ansync offline credit memo processing for Order #' . $item->getIncrementId());
                $this->logger->warning($e->getMessage());
            }
        }

        return $itemsWithJsonAdditionalInfo;
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
