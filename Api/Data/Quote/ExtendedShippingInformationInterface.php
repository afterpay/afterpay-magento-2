<?php

namespace Afterpay\Afterpay\Api\Data\Quote;

interface ExtendedShippingInformationInterface
{
    public const LAST_SELECTED_SHIPPING_RATE = 'last_selected_shipping_rate';

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $param
     * @param mixed $data
     * @return \Magento\Quote\Model\Quote
     */
    public function update(\Magento\Quote\Model\Quote $quote, string $param, $data): \Magento\Quote\Model\Quote;

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $param
     * @return mixed
     */
    public function getParam(\Magento\Quote\Model\Quote $quote, string $param);
}
