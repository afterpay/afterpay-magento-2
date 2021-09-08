<?php declare(strict_types=1);

/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2021 Afterpay https://www.afterpay.com
 */

namespace Afterpay\Afterpay\Model\ExpressPayment;

class ShippingListProvider
{
    private \Magento\Checkout\Api\TotalsInformationManagementInterface $totalsInformationManagement;
    private \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory $totalsInformationFactory;
    private \Afterpay\Afterpay\Model\Adapter\AfterpayExpressPayment $afterpayExpressPayment;
    private \Magento\Quote\Api\ShipmentEstimationInterface $shipmentEstimation;

    public function __construct(
        \Magento\Checkout\Api\TotalsInformationManagementInterface $totalsInformationManagement,
        \Magento\Checkout\Api\Data\TotalsInformationInterfaceFactory $totalsInformationFactory,
        \Afterpay\Afterpay\Model\Adapter\AfterpayExpressPayment $afterpayExpressPayment,
        \Magento\Quote\Api\ShipmentEstimationInterface $shipmentEstimation
    ) {
        $this->totalsInformationManagement = $totalsInformationManagement;
        $this->totalsInformationFactory = $totalsInformationFactory;
        $this->afterpayExpressPayment = $afterpayExpressPayment;
        $this->shipmentEstimation = $shipmentEstimation;
    }

    public function provide(\Magento\Quote\Model\Quote $quote): array
    {
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress(
            $quote->getId(),
            $quote->getShippingAddress()
        );
        $shippingList = [];
        foreach ($shippingMethods as $shippingMethod) {

            /** @var \Magento\Checkout\Api\Data\TotalsInformationInterface $totalsInformation */
            $totalsInformation = $this->totalsInformationFactory->create()
                ->setAddress($quote->getShippingAddress())
                ->setShippingCarrierCode($shippingMethod->getCarrierCode())
                ->setShippingMethodCode($shippingMethod->getMethodCode());

            $quote->setTotalsCollectedFlag(false);
            $calculatedTotals = $this->totalsInformationManagement->calculate($quote->getId(), $totalsInformation);

            if ($this->afterpayExpressPayment->isValidOrderAmount($calculatedTotals->getBaseGrandTotal())) {
                $shippingList[] = $this->createShippingOptionByMethod($shippingMethod, $quote, $calculatedTotals);
            }
        }
        return $shippingList;
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
                'amount' => $this->afterpayExpressPayment->formatAmount($totals->getBaseShippingAmount()),
                'currency' => $quote->getStoreCurrencyCode()
            ],
            'taxAmount' => [
                'amount' => $this->afterpayExpressPayment->formatAmount($totals->getBaseTaxAmount()),
                'currency' => $quote->getStoreCurrencyCode()
            ],
            'orderAmount' => [
                'amount' => $this->afterpayExpressPayment->formatAmount($totals->getBaseGrandTotal()),
                'currency' => $quote->getStoreCurrencyCode()
            ]
        ];
    }
}
