<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\CheckoutManagement;

class RestrictedProductsException extends \Magento\Framework\Validation\ValidationException
{
    private array $restrictedSkus;

    public function __construct(\Magento\Framework\Phrase $phrase, array $restrictedSkus)
    {
        parent::__construct($phrase);
        $this->restrictedSkus = $restrictedSkus;
    }

    public function getRestrictedSkus(): array
    {
        return $this->restrictedSkus;
    }
}
