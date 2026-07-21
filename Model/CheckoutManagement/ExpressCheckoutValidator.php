<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\CheckoutManagement;

class ExpressCheckoutValidator implements \Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface
{
    private \Afterpay\Afterpay\Model\Config $config;
    private \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider
    ) {
        $this->config = $config;
        $this->notAllowedProductsProvider = $notAllowedProductsProvider;
    }

    /**
     * @inheritDoc
     */
    public function validate(\Magento\Quote\Model\Quote $quote): void
    {
        $restrictedSkus = $this->notAllowedProductsProvider->getRestrictedSkusFromQuote($quote);
        sort($restrictedSkus);

        if ($restrictedSkus) {
            throw new RestrictedProductsException(
                __(
                    'Afterpay Express Checkout is unavailable because your cart contains restricted products: %1. '
                    . 'Remove these products from your cart and try again.',
                    implode(', ', $restrictedSkus)
                ),
                $restrictedSkus
            );
        }

        $grandTotal = $quote->getBaseGrandTotal();
        if ($grandTotal < $this->config->getMinOrderTotal() ||
            $grandTotal > $this->config->getMaxOrderTotal()) {
            throw new \Magento\Framework\Validation\ValidationException(
                __('Order amount exceed Afterpay order limit.')
            );
        }
    }
}
