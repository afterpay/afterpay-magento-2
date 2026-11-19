<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit;

class QuoteItemStub extends \Magento\Quote\Model\Quote\Item
{
    public function getProductId()
    {
        return $this->getData('product_id');
    }
}
