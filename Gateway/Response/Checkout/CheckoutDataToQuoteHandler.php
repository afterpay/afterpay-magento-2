<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\Checkout;

class CheckoutDataToQuoteHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private $shippingInformationManagement;
    private $shippingInformationFactory;
    private $addressInterfaceFactory;

    public function __construct(
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement,
        \Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory $shippingInformationFactory,
        \Magento\Quote\Api\Data\AddressInterfaceFactory $addressInterfaceFactory
    ) {
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformationFactory = $shippingInformationFactory;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $paymentDO->getPayment()->getQuote();

        if (!$quote->getCustomerId()) {
            $quote->setCustomerEmail($response['consumer']['email']);
            $quote->setCustomerFirstname($response['consumer']['givenNames']);
            $quote->setCustomerLastname($response['consumer']['surname']);
        }

        /** @var \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformation */
        $shippingInformation = $this->shippingInformationFactory->create();

        /** @var \Magento\Quote\Api\Data\AddressInterface $address */
        $address = $this->addressInterfaceFactory->create();
        $address->setEmail($response['consumer']['email'])
            ->setFirstname($response['consumer']['givenNames'])
            ->setLastname($response['consumer']['surname'])
            ->setTelephone($response['shipping']['phoneNumber'] ?? $response['consumer']['phoneNumber'])
            ->setCity($response['shipping']['area1'])
            ->setCountryId($response['shipping']['countryCode'])
            ->setStreet([$response['shipping']['line1']])
            ->setPostcode($response['shipping']['postcode'])
            ->setRegion($response['shipping']['region'] ?? '');
        if (isset($response['shipping']['line2']) && $streetLine2 = $response['shipping']['line2']) {
            /** @var string[] $street */
            $street = array_merge($address->getStreet(), [$streetLine2]);
            $address->setStreet($street);
        }

        $shippingInformation->setBillingAddress($address);
        if (!$quote->isVirtual()) {
            [$carrierCode, $methodCode] = explode('_', $response['shippingOptionIdentifier']);
            $shippingInformation->setShippingAddress($address);
            $shippingInformation->setShippingCarrierCode($carrierCode);
            $shippingInformation->setShippingMethodCode($methodCode);
        }

        $this->shippingInformationManagement->saveAddressInformation($quote->getId(), $shippingInformation);
    }
}
