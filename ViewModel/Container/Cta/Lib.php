<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\ViewModel\Container\Cta;

class Lib extends \Afterpay\Afterpay\ViewModel\Container\Lib
{
    public function getMinTotalValue(): ?string
    {
        return $this->config->getMinOrderTotal();
    }

    public function getMaxTotalValue(): ?string
    {
        return $this->config->getMaxOrderTotal();
    }
}
