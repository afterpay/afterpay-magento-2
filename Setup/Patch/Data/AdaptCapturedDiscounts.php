<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Setup\Patch\Data;

use Afterpay\Afterpay\Gateway\Config\Config;
use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\PaymentStateInterface;

// @codingStandardsIgnoreFile
class AdaptCapturedDiscounts implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private $salesSetup;
    private $json;
    private $productMetadata;

    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Sales\Setup\SalesSetup $salesSetup,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->productMetadata = $productMetadata;
        $this->salesSetup = $salesSetup;
        $this->json = $json;
    }

    public static function getDependencies()
    {
        return [
            \Afterpay\Afterpay\Setup\Patch\Data\AdaptPayments::class
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        if ($this->productMetadata->getEdition() === 'Community') {
            return;
        }

        $payments = $this->getAfterpayLegacyPaymentsInfo();
        $ordersAdditionalInfo = $this->getNewOrdersAdditionalInfo($payments);
        $this->saveOrdersAdditionalInfo($ordersAdditionalInfo);
        return $this;
    }

    private function saveOrdersAdditionalInfo(array $ordersAdditionalInfo): void
    {
        foreach ($ordersAdditionalInfo as $orderId => $additionalInfo) {
            $this->salesSetup->getConnection()->update(
                $this->salesSetup->getTable('sales_order_payment'),
                ['additional_information' => $this->json->serialize($additionalInfo)],
                ['parent_id = ?' => $orderId]
            );
        }
    }

    private function getNewOrdersAdditionalInfo(array $paymentsInfo): array
    {
        $ordersAdditionalInfo = [];
        foreach ($paymentsInfo as $payment) {
            /** @var array $additionalInfo */
            $additionalInfo = $this->json->unserialize($payment['additional_information']);
            $totalDiscountAmount = ($payment['base_customer_balance_amount'] ?? 0) + ($payment['base_gift_cards_amount'] ?? 0);
            $additionalInfo[AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT] = $totalDiscountAmount;
            if ($additionalInfo[AdditionalInformationInterface::AFTERPAY_PAYMENT_STATE] != PaymentStateInterface::CAPTURED) {
                $additionalInfo[AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT] -=
                    $additionalInfo[AdditionalInformationInterface::AFTERPAY_ROLLOVER_DISCOUNT] ?? 0;
            }
            $ordersAdditionalInfo[$payment['order_id']] = $additionalInfo;
        }
        return $ordersAdditionalInfo;
    }

    private function getAfterpayLegacyPaymentsInfo(): array
    {
        $connection = $this->salesSetup->getConnection();
        $select = $connection->select()
            ->from(
                ['si' => $this->salesSetup->getTable('sales_invoice')],
                ['si.order_id', 'si.base_customer_balance_amount', 'si.base_gift_cards_amount']
            )->joinInner(
                ['sop' => $this->salesSetup->getTable('sales_order_payment')],
                'si.order_id = sop.parent_id AND sop.method = "' . Config::CODE . '"'
                . ' AND sop.additional_information NOT LIKE "%' . AdditionalInformationInterface::AFTERPAY_CAPTURED_DISCOUNT . '%"',
                ['sop.additional_information']
            )->where(
                'si.base_customer_balance_amount IS NOT NULL OR si.base_gift_cards_amount IS NOT NULL'
            );
        return $connection->fetchAll($select);
    }
}
