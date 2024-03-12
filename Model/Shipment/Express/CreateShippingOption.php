<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Shipment\Express;

class CreateShippingOption
{
    use \Magento\Payment\Helper\Formatter;

    private $config;
    private $totalsInformationManagement;
    private $totalsInformationFactory;
    private $checkCBTCurrencyAvailability;
    private $canUseCurrentCurrency = null;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Magento\Checkout\Api\TotalsInformationManagementInterface $totalsInformationManagement,
        \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory $totalsInformationFactory,
        \Afterpay\Afterpay\Model\CBT\CheckCBTCurrencyAvailabilityInterface  $checkCBTCurrencyAvailability
    ) {
        $this->config = $config;
        $this->totalsInformationManagement = $totalsInformationManagement;
        $this->totalsInformationFactory = $totalsInformationFactory;
        $this->checkCBTCurrencyAvailability = $checkCBTCurrencyAvailability;
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
        $isCBTCurrencyAvailable = $this->checkCBTCurrencyAvailability->checkByQuote($quote);
        $currency = $isCBTCurrencyAvailable ? $quote->getQuoteCurrencyCode() : $quote->getStoreCurrencyCode();
        $amount = $isCBTCurrencyAvailable
            ? $quote->getGrandTotal()
            : $quote->getBaseGrandTotal();
        $taxAmount = $isCBTCurrencyAvailable ? $totals->getTaxAmount() : $totals->getBaseTaxAmount();
        $shippingAmount = $this->formatPrice($isCBTCurrencyAvailable
            ? $totals->getShippingAmount()
            : $totals->getBaseShippingAmount());

        return [
            'id' => implode('_', [$shippingMethod->getCarrierCode(), $shippingMethod->getMethodCode()]),
            'name' => !empty($shippingMethod->getCarrierTitle()) ? $shippingMethod->getCarrierTitle() : $shippingMethod->getMethodTitle(),
            'description' => !empty($shippingMethod->getCarrierTitle()) ? $shippingMethod->getCarrierTitle() : $shippingMethod->getMethodTitle(),
            'shippingAmount' => [
                'amount' => $this->formatPrice($shippingAmount),
                'currency' => $currency
            ],
            'taxAmount' => [
                'amount' => $this->formatPrice($taxAmount),
                'currency' => $currency
            ],
            'orderAmount' => [
                'amount' => $this->formatPrice($amount),
                'currency' => $currency
            ]
        ];
    }
}
