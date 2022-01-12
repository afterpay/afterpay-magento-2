<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Shipment\Express;

class ShippingListProvider
{
    private \Magento\Quote\Api\ShipmentEstimationInterface $shipmentEstimation;
    private CreateShippingOption $createShippingOption;

    public function __construct(
        CreateShippingOption $createShippingOption,
        \Magento\Quote\Api\ShipmentEstimationInterface $shipmentEstimation
    ) {
        $this->createShippingOption = $createShippingOption;
        $this->shipmentEstimation = $shipmentEstimation;
    }

    public function provide(\Magento\Quote\Model\Quote $quote): array
    {
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress(
            $quote->getId(),
            $quote->getShippingAddress()
        );
        $shippingOptions = [];
        foreach ($shippingMethods as $shippingMethod) {
            if (!$shippingMethod->getAvailable()) {
                continue;
            }
            $shippingOption = $this->createShippingOption->create($quote, $shippingMethod);
            if ($shippingOption != null) {
                $shippingOptions[] = $shippingOption;
            }
        }
        return $shippingOptions;
    }
}
