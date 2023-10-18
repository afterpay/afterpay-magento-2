<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

use Afterpay\Afterpay\Api\Data\TokenInterface;
use Afterpay\Afterpay\Model\ResourceModel\Token as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel implements TokenInterface
{
    protected $_eventPrefix = 'afterpay_tokens_log_model';

    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
