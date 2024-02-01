<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Checkout\Model\TotalsInformationManagement;

use Afterpay\Afterpay\Api\Data\Quote\ExtendedShippingInformationInterface;
use Afterpay\Afterpay\Model\Config;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;

class AddLastSelectedShippingRate
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ExtendedShippingInformationInterface
     */
    private $extendedShippingInformation;

    /**
     * @var Config;
     */
    private $config;

    public function __construct(
        CartRepositoryInterface              $cartRepository,
        ExtendedShippingInformationInterface $extendedShippingInformation,
        Config                               $config
    ) {
        $this->cartRepository = $cartRepository;
        $this->extendedShippingInformation = $extendedShippingInformation;
        $this->config = $config;
    }

    public function afterCalculate(
        TotalsInformationManagementInterface $subject,
        TotalsInterface                      $result,
                                             $cartId,
        TotalsInformationInterface           $addressInformation
    ) {
        if (!$this->config->getAddLastSelectedShipRate()
            || (!$this->config->getIsEnableExpressCheckoutMiniCart()
                && !$this->config->getIsEnableExpressCheckoutProductPage()
                && !$this->config->getIsEnableExpressCheckoutCartPage())) {
            return $result;
        }

        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $shippingRate = '';
        if ($quote->getShippingAddress()->getShippingMethod()) {
            $shippingRate = $quote->getShippingAddress()->getShippingMethod();
        } elseif ($addressInformation->getShippingCarrierCode() && $addressInformation->getShippingMethodCode()) {
            $shippingRate = implode(
                '_',
                [$addressInformation->getShippingCarrierCode(), $addressInformation->getShippingMethodCode()]
            );
        }

        if ($shippingRate) {
            $this->extendedShippingInformation->update(
                $quote,
                ExtendedShippingInformationInterface::LAST_SELECTED_SHIPPING_RATE,
                $shippingRate
            );
        }

        return $result;
    }
}
