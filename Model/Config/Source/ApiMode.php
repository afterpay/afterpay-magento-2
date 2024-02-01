<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Config\Source;

class ApiMode implements \Magento\Framework\Data\OptionSourceInterface
{
    public const SANDBOX = 'sandbox';
    public const PRODUCTION = 'production';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::SANDBOX,
                'label' => __('Sandbox')
            ],
            [
                'value' => self::PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
