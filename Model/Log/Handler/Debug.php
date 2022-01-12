<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Log\Handler;

class Debug extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = "/var/log/afterpay.log";

    protected $loggerType = \Monolog\Logger::DEBUG;
}
