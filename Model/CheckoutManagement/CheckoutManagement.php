<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\CheckoutManagement;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Api\Data\RedirectPathInterface;
use Magento\Authorization\Model\UserContextInterface;

class CheckoutManagement implements \Afterpay\Afterpay\Api\CheckoutManagementInterface
{
    private $checkoutCommand;
    private $expressCheckoutCommand;
    private $cartRepository;
    private $maskedQuoteIdToQuoteId;
    private $checkoutFactory;
    private $userContext;
    private $expressCheckoutValidator;
    private $checkoutValidator;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $checkoutCommand,
        \Magento\Payment\Gateway\CommandInterface $expressCheckoutCommand,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        \Afterpay\Afterpay\Api\Data\CheckoutInterfaceFactory $checkoutFactory,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        ?\Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface $checkoutValidator = null,
        ?\Afterpay\Afterpay\Model\Spi\CheckoutValidatorInterface $expressCheckoutValidator = null
    ) {
        $this->checkoutCommand = $checkoutCommand;
        $this->expressCheckoutCommand = $expressCheckoutCommand;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->checkoutFactory = $checkoutFactory;
        $this->userContext = $userContext;
        $this->checkoutValidator = $checkoutValidator;
        $this->expressCheckoutValidator = $expressCheckoutValidator;
    }

    public function create(string $cartId, RedirectPathInterface $redirectPath): CheckoutInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getAuthorizedActiveQuote($cartId);

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

    /**
     * Resolve the cart for a checkout request, refusing callers who do not own it.
     *
     * A masked cart id is an unguessable per-cart capability, so it is accepted as-is. A numeric
     * quote id is sequential and guessable, so it is only accepted from the authenticated customer
     * who owns that quote. Every refusal raises the same exception as an unknown cart, so the
     * endpoint cannot be used to tell an existing cart apart from someone else's.
     *
     * @param string $cartId
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAuthorizedActiveQuote(string $cartId): \Magento\Quote\Api\Data\CartInterface
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->getCustomerOwnedActiveQuote($cartId);
        }

        return $this->cartRepository->getActive($quoteId);
    }

    /**
     * Resolve a numeric quote id, but only for the authenticated customer who owns it.
     *
     * @param string $cartId
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerOwnedActiveQuote(string $cartId): \Magento\Quote\Api\Data\CartInterface
    {
        $customerId = $this->getAuthenticatedCustomerId();
        if ($customerId === null || !ctype_digit($cartId)) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('cartId', $cartId);
        }

        try {
            $quote = $this->cartRepository->getActive((int)$cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('cartId', $cartId);
        }

        if ((int)$quote->getCustomerId() !== $customerId) {
            throw \Magento\Framework\Exception\NoSuchEntityException::singleField('cartId', $cartId);
        }

        return $quote;
    }

    /**
     * Customer id of the caller, or null when the caller is not an authenticated customer.
     *
     * @return int|null
     */
    private function getAuthenticatedCustomerId(): ?int
    {
        if ($this->userContext->getUserType() !== UserContextInterface::USER_TYPE_CUSTOMER) {
            return null;
        }

        $customerId = (int)$this->userContext->getUserId();

        return $customerId > 0 ? $customerId : null;
    }
}
