<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Spi;

interface CheckoutValidatorInterface
{
    /**
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function validate(\Magento\Quote\Model\Quote $quote): void;
}
