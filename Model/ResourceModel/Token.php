<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Model\ResourceModel;

use Afterpay\Afterpay\Api\Data\TokenInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Token extends AbstractDb
{
    protected $_eventPrefix = 'afterpay_tokens_log_resource_model';

    protected function _construct()
    {
        $this->_init('afterpay_tokens_log', TokenInterface::LOG_ID_FIELD);
        $this->_useIsObjectNew = true;
    }

    public function selectByToken(string $token): string
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where(TokenInterface::TOKEN_FIELD . ' = ?', $token);

        $result = $connection->fetchOne($select);

        return is_string($result) ? $result : '';
    }

    public function insertNewToken(int $orderId, string $token, ?string $expiryDate): int
    {
        return $this->getConnection()->insert(
            $this->getMainTable(),
            [
                TokenInterface::ORDER_ID_FIELD        => $orderId,
                TokenInterface::TOKEN_FIELD           => $token,
                TokenInterface::EXPIRATION_DATE_FIELD => $expiryDate,
            ]);
    }
}
