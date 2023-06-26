<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CBTAvailableCurrencies extends \Magento\Config\Block\System\Config\Form\Field
{
    private $serializer;
    private $logger;

    public function __construct(
        LoggerInterface     $logger,
        SerializerInterface $serializer,
        Context             $context,
        array               $data = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        try {
            $CbtAvailableCurrencies = $this->serializer->unserialize($element->getValue());
            $newValue = '';
            if (!$CbtAvailableCurrencies) {
                return parent::_renderValue($element);
            }

            foreach ($CbtAvailableCurrencies as $currencyCode => $currency) {
                $newValue .= $currencyCode . '(min:' . $currency['minimumAmount']['amount']
                    . ',max:' . $currency['maximumAmount']['amount'] . ') ';
            }
            $element->setValue($newValue);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return parent::_renderValue($element);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @phpstan-ignore-next-line */
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @phpstan-ignore-next-line */
        $element->setDisabled('disabled');
        return $element->getElementHtml();
    }
}
