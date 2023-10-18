<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Model\ResourceModel\Token;

use Afterpay\Afterpay\Model\ResourceModel\Token as ResourceModel;
use Afterpay\Afterpay\Model\Token as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_eventPrefix = 'afterpay_tokens_log_collection';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
