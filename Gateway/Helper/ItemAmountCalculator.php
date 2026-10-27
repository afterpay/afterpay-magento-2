<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Helper;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Calculates the quantity and price sent to the API for a quote item.
 * Shared by the checkout request builder and the response validation handler so both always agree.
 */
class ItemAmountCalculator
{
    private PriceCurrencyInterface $priceCurrency;

    public function __construct(PriceCurrencyInterface $priceCurrency)
    {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Items with a fractional qty are sent as qty 1 with the row amount, since the API accepts integer qty only.
     *
     * @return array{qty: int, amount: float, discount: float} amount is rounded to the currency precision
     */
    public function calculate(Item $item, bool $isCbtCurrency): array
    {
        $amount = (float)($isCbtCurrency ? $item->getPriceInclTax() : $item->getBasePriceInclTax());
        $discount = 0.0;
        if ($item->getDiscountAmount() !== null) {
            $discount = (float)($isCbtCurrency ? $item->getDiscountAmount() : $item->getBaseDiscountAmount());
            $amount -= $discount;
        }

        $qty = (float)$item->getQty();
        if (floor($qty) == $qty) {
            return ['qty' => (int)$qty, 'amount' => $this->priceCurrency->round($amount), 'discount' => $discount];
        }

        return ['qty' => 1, 'amount' => $this->priceCurrency->round($amount * $qty), 'discount' => $discount];
    }
}
