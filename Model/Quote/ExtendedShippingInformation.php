<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Quote;

class ExtendedShippingInformation implements \Afterpay\Afterpay\Api\Data\Quote\ExtendedShippingInformationInterface
{
    private \Magento\Quote\Api\CartRepositoryInterface $cartRepository;
    private \Magento\Framework\Serialize\SerializerInterface $serializer;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->cartRepository = $cartRepository;
        $this->serializer = $serializer;
    }

    public function update(\Magento\Quote\Model\Quote $quote, string $param, $data): \Magento\Quote\Model\Quote
    {
        $extShippingInfo = $quote->getExtShippingInfo();
        if ($extShippingInfo) {
            $extShippingInfo = $this->serializer->unserialize($extShippingInfo);
        }

        if (!$extShippingInfo) {
            $extShippingInfo = [];
        }

        if (is_array($extShippingInfo)) {
            $extShippingInfo[$param] = $data;
            $quote->setExtShippingInfo($this->serializer->serialize($extShippingInfo));

            $this->cartRepository->save($quote);
        }

        return $quote;
    }

    public function getParam(\Magento\Quote\Model\Quote $quote, string $param)
    {
        $extShippingInfo = $quote->getExtShippingInfo();

        if ($extShippingInfo) {
            $extShippingInfo = $this->serializer->unserialize($extShippingInfo);

            if (isset($extShippingInfo[$param])) {
                return $extShippingInfo[$param];
            }
        }

        return $extShippingInfo;
    }
}
