<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\Checkout;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class CheckoutItemsAmountValidationHandler implements HandlerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        /** @var Quote $quote */
        $quote = $payment->getQuote();
        $isCBTCurrency = $payment->getAdditionalInformation(CheckoutInterface::AFTERPAY_IS_CBT_CURRENCY);
        $grandTotal = $isCBTCurrency ? $quote->getGrandTotal() : $quote->getBaseGrandTotal();

        if (round(1 * $grandTotal, 2) != round(1 * $response['amount']['amount'], 2)) {
            $invalidAmountExceptionMessage = __('There was a issue with the processing of your payment. Invalid amount.');
            $this->logger->warning($invalidAmountExceptionMessage);
            $this->logger->warning('Invalid Amount Cart ID: ' . $quote->getId());
            throw new LocalizedException($invalidAmountExceptionMessage);
        }

        $quoteItems = $quote->getAllVisibleItems();
        $responseItems = $response['items'];

        $invalidCartItemsExceptionMessage = __('There was a issue with the processing of your payment. Invalid cart items.');

        if (count($quoteItems) != count($responseItems)) {
            $this->logger->warning($invalidCartItemsExceptionMessage);
            $this->logger->warning('Invalid Items Cart ID: ' . $quote->getId());
            throw new LocalizedException($invalidCartItemsExceptionMessage);
        }

        $responseItemsSkus = array_column($responseItems, 'sku');
        foreach ($quoteItems as $item) {
            if ($item->getProduct()->getTypeId() === Giftcard::TYPE_GIFTCARD) {
                $itemFound = false;
                $amount = $isCBTCurrency ? $item->getPriceInclTax() : $item->getBasePriceInclTax();
                foreach ($responseItems as $responseItemIndex => $responseItem) {
                    if ($responseItem['sku'] == $item->getSku()
                        && $responseItem['quantity'] == $item->getQty()
                        && $responseItem['price']['amount'] == $amount) {
                        unset($responseItems[$responseItemIndex], $responseItemsSkus[$responseItemIndex]);
                        $itemFound = true;
                        break;
                    }
                }
                if ($itemFound === false) {
                    $this->logger->warning($invalidCartItemsExceptionMessage);
                    $this->logger->warning('Invalid Items Cart ID (giftcard): ' . $quote->getId());
                    throw new LocalizedException($invalidCartItemsExceptionMessage);
                }
            } else {
                $itemIndex = array_search($item->getSku(), $responseItemsSkus);
                if ($itemIndex === false) {
                    throw new LocalizedException($invalidCartItemsExceptionMessage);
                }
                if ($item->getQty() != $responseItems[$itemIndex]['quantity']) {
                    $qty = $item->getQty();
                    $isIntQty = floor($qty) == $qty;
                    if (!$isIntQty && $responseItems[$itemIndex]['quantity'] == 1) {
                        $amount = $isCBTCurrency ? $item->getPriceInclTax() : $item->getBasePriceInclTax();
                        if ($amount * $qty == $responseItems[$itemIndex]['price']['amount']) {
                            continue;
                        }
                    }
                    $this->logger->warning($invalidCartItemsExceptionMessage);
                    $this->logger->warning('Invalid Items Cart ID: ' . $quote->getId());
                    throw new LocalizedException($invalidCartItemsExceptionMessage);
                }
            }
        }
    }
}
