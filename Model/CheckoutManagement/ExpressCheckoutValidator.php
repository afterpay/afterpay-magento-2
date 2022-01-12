<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\CheckoutManagement;

class ExpressCheckoutValidator implements \Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface
{
    private \Afterpay\Afterpay\Model\Config $config;

    public function __construct(
        \Afterpay\Afterpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function validate(\Magento\Quote\Model\Quote $quote): void
    {
        $grandTotal = $quote->getBaseGrandTotal();
        if ($grandTotal < $this->config->getMinOrderTotal() ||
            $grandTotal > $this->config->getMaxOrderTotal()) {
            throw new \Magento\Framework\Validation\ValidationException(
                __('Order amount exceed Afterpay order limit.')
            );
        }
    }
}
