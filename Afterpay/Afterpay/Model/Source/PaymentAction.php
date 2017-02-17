<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model\Source;
use \Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 * @package Afterpay\Afterpay\Model\Source
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible actions on order place
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Order'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorise and Capture'),
            ]
        ];
    }
}