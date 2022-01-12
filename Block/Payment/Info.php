<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Payment;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    public function toPdf(): string
    {
        $this->setData('is_pdf', true);
        return parent::toPdf();
    }

    public function getSpecificInformation(): array
    {
        if ($this->getData('is_pdf')) {
            return [];
        }
        return parent::getSpecificInformation();
    }

    protected function getLabel($field)
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
