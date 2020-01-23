<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Source;

/**
 * Class PaymentFlow
 * @package Afterpay\Afterpay\Model\Source
 */
class PaymentFlow implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * PaymentFlow constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
			['value' => 'immediate', 'label' => __('Immediate Payment Flow')],
			['value' => 'deferred', 'label' => __('Deferred Payment Flow')],
		];
    }
}
