<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Logger;

use Monolog\Logger as MonoLogger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = MonoLogger::DEBUG;

    protected $fileName = '/var/log/afterpay.log';
}
