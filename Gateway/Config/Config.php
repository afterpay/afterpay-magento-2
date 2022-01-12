<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'afterpay';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig, self::CODE);
    }
}
