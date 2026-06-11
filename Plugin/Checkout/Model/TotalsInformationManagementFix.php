<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Checkout\Model;

use Afterpay\Afterpay\Model\Config;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Model\TotalsInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;

class TotalsInformationManagementFix
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param Config $config
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        Config $config
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->config = $config;
    }

    /**
     * @param TotalsInformationManagement $subject
     * @param callable $proceed
     * @param int $cartId
     * @param TotalsInformationInterface $addressInformation
     */
    public function aroundCalculate(
        TotalsInformationManagement $subject,
        callable $proceed,
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        if ($this->config->getIsEnableExpressCheckoutCartPage()
            || $this->config->getIsEnableExpressCheckoutMiniCart()) {
            $quote = $this->cartRepository->get($cartId);
            $this->validateQuote($quote);

            if ($quote->getIsVirtual()) {
                $quote->setBillingAddress($addressInformation->getAddress());
            } else {
                $quote->setShippingAddress($addressInformation->getAddress());
                if ($addressInformation->getShippingCarrierCode() && $addressInformation->getShippingMethodCode()) {
                    $shippingMethod = implode(
                        '_',
                        [$addressInformation->getShippingCarrierCode(), $addressInformation->getShippingMethodCode()]
                    );
                    $quoteShippingAddress = $quote->getShippingAddress();
                    if ($quoteShippingAddress->getShippingMethod() &&
                        $quoteShippingAddress->getShippingMethod() !== $shippingMethod
                    ) {
                        $quoteShippingAddress->setShippingAmount(0);
                        $quoteShippingAddress->setBaseShippingAmount(0);
                    }
                    $quoteShippingAddress->setCollectShippingRates(true)
                        ->setShippingMethod($shippingMethod);
                    $quoteShippingAddress->save();
                }
            }
            $quote->collectTotals();

            return $this->cartTotalRepository->get($cartId);
        }
        return $proceed($cartId, $addressInformation);
    }


    /**
     * Check if quote have items.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Totals calculation is not applicable to empty cart')
            );
        }
    }
}
