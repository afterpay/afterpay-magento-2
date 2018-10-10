<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 * Updated on 27th March 2018
 * Removed API V0 functionality
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
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorise and Capture'),
            ]
        ];
    }
}