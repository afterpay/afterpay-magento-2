<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Button;

class LimitUpdate extends \Magento\Config\Block\System\Config\Form\Field
{
    public const TEMPLATE = 'Afterpay_Afterpay::system/config/button/update.phtml';

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @phpstan-ignore-next-line */
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /** @inheritDoc  */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }

        return $this;
    }

    /** @inheritDoc  */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getData('original_data');
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'button_url' => $this->getUrl($originalData['button_url']),
                'html_id' => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }
}
