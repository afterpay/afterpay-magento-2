<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Button;

class LimitUpdate extends \Magento\Config\Block\System\Config\Form\Field
{
    public const TEMPLATE = 'Afterpay_Afterpay::system/config/button/update.phtml';

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsetData('scope')->unsetData('can_use_website_value')->unsetData('can_use_default_value');
        return parent::render($element);
    }

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        ?\Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer = null
    ) {
        $data['template'] = ($data['template'] ?? null) ?: static::TEMPLATE;
        parent::__construct($context, $data, $secureRenderer);
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
