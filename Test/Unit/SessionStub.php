<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit;

class SessionStub extends \Magento\Checkout\Model\Session
{
    public function setData($key, $value = null)
    {
        return $this->__call('setData', [$key, $value]);
    }
}
