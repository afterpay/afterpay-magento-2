<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Shipment\Express;

class CreateShippingOption
{
    use \Magento\Payment\Helper\Formatter;

    private $config;
    private $totalsInformationManagement;
    private $totalsInformationFactory;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Checkout\Api\TotalsInformationManagementInterface $totalsInformationManagement,
        \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory $totalsInformationFactory
    ) {
        $this->config = $config;
        $this->totalsInformationManagement = $totalsInformationManagement;
        $this->totalsInformationFactory = $totalsInformationFactory;
    }

    public function create(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingMethodInterface $shippingMethod
    ): ?array {
        $totalsInformation = $this->totalsInformationFactory->create()
            ->setAddress($quote->getShippingAddress())
            ->setShippingCarrierCode($shippingMethod->getCarrierCode())
            ->setShippingMethodCode($shippingMethod->getMethodCode());

        $quote->setData('totals_collected_flag', false);
        $calculatedTotals = $this->totalsInformationManagement->calculate(
            $quote->getId(),
            $totalsInformation
        );

        if ($calculatedTotals->getBaseGrandTotal() > $this->config->getMinOrderTotal() &&
            $calculatedTotals->getBaseGrandTotal() < $this->config->getMaxOrderTotal()) {
            return $this->createShippingOptionByMethod($shippingMethod, $quote, $calculatedTotals);
        }
        return null;
    }

    private function createShippingOptionByMethod(
        \Magento\Quote\Api\Data\ShippingMethodInterface $shippingMethod,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\TotalsInterface $totals
    ): array {
        return [
            'id' => $shippingMethod->getCarrierCode() . "_" . $shippingMethod->getMethodCode(),
            'name' => $shippingMethod->getCarrierTitle(),
            'description' => $shippingMethod->getCarrierTitle(),
            'shippingAmount' => [
                'amount' => $this->formatPrice($totals->getBaseShippingAmount()),
                'currency' => $quote->getStoreCurrencyCode()
            ],
            'taxAmount' => [
                'amount' => $this->formatPrice($totals->getBaseTaxAmount()),
                'currency' => $quote->getStoreCurrencyCode()
            ],
            'orderAmount' => [
                'amount' => $this->formatPrice($totals->getBaseGrandTotal()),
                'currency' => $quote->getStoreCurrencyCode()
            ]
        ];
    }
}
