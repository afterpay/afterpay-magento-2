<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\Checkout;

class CheckoutDataToQuoteHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    private \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement;
    private \Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory $shippingInformationFactory;
    private \Magento\Quote\Api\Data\AddressInterfaceFactory $addressInterfaceFactory;

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

        $consumerEmail = $response['consumer']['email'];
        $consumerName = $response['consumer']['givenNames'];
        $consumerLastname = $response['consumer']['surname'];
        if (!$quote->getCustomerId()) {
            $quote->setCustomerEmail($consumerEmail);
            $quote->setCustomerFirstname($consumerName);
            $quote->setCustomerLastname($consumerLastname);
        }

        /** @var \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformation */
        $shippingInformation = $this->shippingInformationFactory->create();

        if (!empty($response['shipping']['name'])) {
            $nameArray = explode(' ', $response['shipping']['name']);
            $firstname = $nameArray[0] ?? $consumerName;
            if (!empty($nameArray[1])) {
                $lastname = implode(' ', array_slice($nameArray, 1));
            } else {
                $lastname = $firstname;
            }
        } else {
            $firstname = $consumerName;
            $lastname = $consumerLastname;
        }

        /** @var \Magento\Quote\Api\Data\AddressInterface $address */
        $address = $this->addressInterfaceFactory->create();
        $address->setEmail($consumerEmail)
            ->setFirstname($firstname)
            ->setLastname($lastname)
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
        if ($quote->isVirtual()) {
            $shippingInformation->setShippingAddress($address); // to avoid an error with gift cart registry
        } else {
            $explodedShippingOption = explode('_', $response['shippingOptionIdentifier']);
            $carrierCode = array_shift($explodedShippingOption);
            $methodCode = implode('_', $explodedShippingOption);
            $shippingInformation->setShippingAddress($address);
            $shippingInformation->setShippingCarrierCode($carrierCode);
            $shippingInformation->setShippingMethodCode($methodCode);
        }

        $this->shippingInformationManagement->saveAddressInformation($quote->getId(), $shippingInformation);
    }
}
