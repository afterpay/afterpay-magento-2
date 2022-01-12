<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\CheckoutManagement;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Api\Data\RedirectPathInterface;

class CheckoutManagement implements \Afterpay\Afterpay\Api\CheckoutManagementInterface
{
    private \Magento\Payment\Gateway\CommandInterface $checkoutCommand;
    private \Magento\Payment\Gateway\CommandInterface $expressCheckoutCommand;
    private \Magento\Quote\Api\CartRepositoryInterface $cartRepository;
    private \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;
    private \Afterpay\Afterpay\Api\Data\CheckoutInterfaceFactory $checkoutFactory;
    private ?\Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface $expressCheckoutValidator;
    private ?\Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface $checkoutValidator;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $checkoutCommand,
        \Magento\Payment\Gateway\CommandInterface $expressCheckoutCommand,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        \Afterpay\Afterpay\Api\Data\CheckoutInterfaceFactory $checkoutFactory,
        ?\Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface $checkoutValidator = null,
        ?\Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface $expressCheckoutValidator = null
    ) {
        $this->checkoutCommand = $checkoutCommand;
        $this->expressCheckoutCommand = $expressCheckoutCommand;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->checkoutFactory = $checkoutFactory;
        $this->checkoutValidator = $checkoutValidator;
        $this->expressCheckoutValidator = $expressCheckoutValidator;
    }

    public function create(string $cartId, RedirectPathInterface $redirectPath): CheckoutInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getActiveQuoteByCartOrQuoteId($cartId);

        $this->cartRepository->save($quote->reserveOrderId());
        if ($this->checkoutValidator !== null) {
            $this->checkoutValidator->validate($quote);
        }
        $this->checkoutCommand->execute(['quote' => $quote, 'redirect_path' => $redirectPath]);

        return $this->createCheckout($quote->getPayment());
    }

    public function createExpress(string $cartId, string $popupOriginUrl): CheckoutInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getActiveQuoteByCartOrQuoteId($cartId);

        $this->cartRepository->save($quote->reserveOrderId());
        if ($this->expressCheckoutValidator !== null) {
            $this->expressCheckoutValidator->validate($quote);
        }
        $this->expressCheckoutCommand->execute(['quote' => $quote, 'popup_origin_url' => $popupOriginUrl]);

        return $this->createCheckout($quote->getPayment());
    }

    private function createCheckout(\Magento\Payment\Model\InfoInterface $payment): CheckoutInterface
    {
        return $this->checkoutFactory->create()
            ->setAfterpayToken(
                $payment->getAdditionalInformation(CheckoutInterface::AFTERPAY_TOKEN)
            )->setAfterpayAuthTokenExpires(
                $payment->getAdditionalInformation(CheckoutInterface::AFTERPAY_AUTH_TOKEN_EXPIRES)
            )->setAfterpayRedirectCheckoutUrl(
                $payment->getAdditionalInformation(CheckoutInterface::AFTERPAY_REDIRECT_CHECKOUT_URL)
            );
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getActiveQuoteByCartOrQuoteId(string $cartId): \Magento\Quote\Api\Data\CartInterface
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $quoteId = (int)$cartId;
        }
        return $this->cartRepository->getActive($quoteId);
    }
}
