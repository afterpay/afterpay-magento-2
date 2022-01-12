<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Checkout\CustomerData;

class Cart
{
    /**
     * @param array $result
     * @return array
     * @SuppressWarnings("unused")
     */
    public function afterGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        $result,
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $result['is_virtual'] = $item->getProduct()->getIsVirtual();
        return $result;
    }
}
