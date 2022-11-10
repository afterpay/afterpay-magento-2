<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\Checkout;

class CheckoutItemsAmountValidationHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $payment->getQuote();
        $isCBTCurrency = $payment->getAdditionalInformation(\Afterpay\Afterpay\Api\Data\CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);
        $grandTotal = $isCBTCurrency ? $quote->getGrandTotal() : $quote->getBaseGrandTotal();

        if (round(1 * $grandTotal, 2) != round(1 * $response['amount']['amount'], 2)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('There are issues when processing your payment. Invalid Amount')
            );
        }

        $quoteItems = $quote->getAllVisibleItems();
        $responseItems = $response['items'];

        $invalidCartItemsExceptionMessage = __('There are issues when processing your payment. Invalid Cart Items');

        if (count($quoteItems) != count($responseItems)) {
            throw new \Magento\Framework\Exception\LocalizedException($invalidCartItemsExceptionMessage);
        }

        $responseItemsSkus = array_column($responseItems, 'sku');
        foreach ($quoteItems as $item) {
            $itemIndex = array_search($item->getSku(), $responseItemsSkus);
            if ($itemIndex === false) {
                throw new \Magento\Framework\Exception\LocalizedException($invalidCartItemsExceptionMessage);
            }
            if ($item->getQty() != $responseItems[$itemIndex]['quantity']) {
                throw new \Magento\Framework\Exception\LocalizedException($invalidCartItemsExceptionMessage);
            }
        }
    }
}
