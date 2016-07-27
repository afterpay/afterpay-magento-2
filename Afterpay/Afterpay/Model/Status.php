<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
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