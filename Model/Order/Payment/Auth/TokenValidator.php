<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\Payment\Auth;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Gateway\Config\Config;
use Magento\Framework\App\ResourceConnection;

class TokenValidator
{
    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function checkIsUsed(string $token): bool
    {
        $salesOrderPaymentTable = $this->resourceConnection->getConnection()->getTableName('sales_order_payment');
        $checkSelect = $this->resourceConnection->getConnection()->select()
            ->from($salesOrderPaymentTable)
            ->where('method = ?', Config::CODE)
            ->where('base_amount_paid_online IS NOT NULL')
            ->where('last_trans_id IS NOT NULL')
            ->where('additional_information like ?', '%"' . CheckoutInterface::AFTERPAY_TOKEN . '":"' . $token . '%');

        return (bool)$this->resourceConnection->getConnection()->fetchOne($checkSelect);
    }
}
