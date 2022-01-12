<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Checks;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class CanUseForQuoteItems implements \Magento\Payment\Model\Checks\SpecificationInterface
{
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote): bool
    {
        try {
            $quoteItemsValidator = $paymentMethod->getValidatorPool()->get('quote_items');
        } catch (\Throwable $e) {
            return true;
        }

        $result = $quoteItemsValidator->validate(['quote' => $quote]);
        return $result->isValid();
    }
}
