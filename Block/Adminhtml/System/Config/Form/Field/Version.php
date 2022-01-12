<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    const MODULE_NAME = "Afterpay_Afterpay";

    private $resource;

    public function __construct(
        \Magento\Framework\Module\ResourceInterface $resource,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
