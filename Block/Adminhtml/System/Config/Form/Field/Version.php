<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    public const MODULE_NAME = "Afterpay_Afterpay";

    private \Magento\Framework\Module\ResourceInterface $resource;

    public function __construct(
        \Magento\Framework\Module\ResourceInterface $resource,
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->resource->getDataVersion(self::MODULE_NAME) ?: "";
    }
}
