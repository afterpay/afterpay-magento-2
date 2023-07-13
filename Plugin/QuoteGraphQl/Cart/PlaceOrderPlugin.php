<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\QuoteGraphQl\Cart;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Gateway\Config\Config;
use Afterpay\Afterpay\Model\Payment\Capture\PlaceOrderProcessor;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderModel;

class PlaceOrderPlugin
{
    private PlaceOrderProcessor $placeOrderProcessor;
    private CommandInterface $validateCheckoutDataCommand;

    public function __construct(
        PlaceOrderProcessor $placeOrderProcessor,
        CommandInterface    $validateCheckoutDataCommand
    ) {
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->validateCheckoutDataCommand = $validateCheckoutDataCommand;
    }

    public function aroundExecute(
        PlaceOrderModel $subject,
        callable        $proceed,
        Quote           $cart,
        string          $maskedCartId,
        int             $userId
    ): int {
        $payment = $cart->getPayment();
        if ($payment->getMethod() === Config::CODE) {
            $afterpayOrderToken = $payment->getAdditionalInformation(CheckoutInterface::AFTERPAY_TOKEN);

            return $this->placeOrderProcessor->execute($cart, $this->validateCheckoutDataCommand, $afterpayOrderToken);
        }

        return $proceed($cart, $maskedCartId, $userId);
    }
}
