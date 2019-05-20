<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model;

/**
 * Class Status
 * @package Afterpay\Afterpay\Model
 */
class Status
{
    /**
     * Constant variable to manage status responds
     */
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_DECLINED = 'DECLINED';
    const STATUS_FAILED = 'FAILED';
}
