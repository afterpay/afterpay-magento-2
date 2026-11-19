<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit;

class QuoteStub extends \Magento\Quote\Model\Quote
{
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    public function getGrandTotal()
    {
        return $this->getData('grand_total');
    }

    public function getBaseGrandTotal()
    {
        return $this->getData('base_grand_total');
    }
}
