<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Model\Checks;

class SpecificationFactory
{
    /**
     * @var array
     */
    private $additionalChecks;

    public function __construct(array $additionalChecks = []) {
        $this->additionalChecks = $additionalChecks;
    }

    public function beforeCreate(
        \Magento\Payment\Model\Checks\SpecificationFactory $specificationFactory,
        $data
    ) {
        return [array_merge($data, $this->additionalChecks)];
    }
}
