<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Shipment\Express;

class ShippingAddressUpdater
{
    const SHIPPING_ADDRESS_KEYS = [
        'name',
        'address1',
        'address2',
        'countryCode',
        'suburb',
        'postcode',
        'state',
        'phoneNumber'
    ];
    private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository;
    private \Magento\Directory\Model\Region $region;

    public function __construct(
        \Magento\Directory\Model\Region $region,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->region = $region;
        $this->quoteRepository = $quoteRepository;
    }

    public function fillQuoteWithShippingAddress(
        array $shippingAddress,
        \Magento\Quote\Model\Quote $quote
    ): \Magento\Quote\Model\Quote {
        foreach (self::SHIPPING_ADDRESS_KEYS as $shippingAddressKey) {
            if (!isset($shippingAddress[$shippingAddressKey])) {
                throw new \InvalidArgumentException(
                    "The shipping address does not contain " . $shippingAddressKey
                );
            }
        }

        $quoteShippingAddress = $quote->getShippingAddress();
        if (!empty($shippingAddress) && !$quote->isVirtual()) {
            $fullName = explode(' ', $shippingAddress['name']);
            $quoteShippingAddress->setFirstname($fullName[0]);
            if (isset($fullName[1])) {
                $quoteShippingAddress->setLastName($fullName[1]);
            }
            $quoteShippingAddress->setStreet([
                $shippingAddress['address1'],
                $shippingAddress['address2']
            ]);
            $quoteShippingAddress->setCountryId($shippingAddress['countryCode']);
            $quoteShippingAddress->setCity($shippingAddress['suburb']);
            $quoteShippingAddress->setPostcode($shippingAddress['postcode']);
            $quoteShippingAddress->setRegionId(
                $this->region->loadByCode($shippingAddress['state'], $shippingAddress['countryCode'])
                    ->getId()
            );
            $quoteShippingAddress->setRegion($shippingAddress['state']);
            $quoteShippingAddress->setTelephone($shippingAddress['phoneNumber']);
            $quoteShippingAddress->setCollectShippingRates(true);
            $quote->setShippingAddress($quoteShippingAddress);
            $this->quoteRepository->save($quote);
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteRepository->getActive($quote->getId());
        }
        return $quote;
    }
}
