<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Api\Data;

/**
 * Interface CheckoutInterface
 * @api
 */
interface CheckoutInterface
{
    /**#@+
     * Checkout result keys
     */
    const AFTERPAY_TOKEN = 'afterpay_token';
    const AFTERPAY_AUTH_TOKEN_EXPIRES = 'afterpay_expires';
    const AFTERPAY_REDIRECT_CHECKOUT_URL = 'afterpay_redirectCheckoutUrl';
    const AFTERPAY_IS_CBT_CURRENCY = 'afterpay_is_cbt_currency';
    const AFTERPAY_CBT_CURRENCY = 'afterpay_cbt_currency';
    /**#@-*/

    /**
     * @param string $token
     * @return static
     */
    public function setAfterpayToken(string $token): self;

    /**
     * @return string
     */
    public function getAfterpayToken(): string;

    /**
     * @param string $authTokenExpires
     * @return static
     */
    public function setAfterpayAuthTokenExpires(string $authTokenExpires): self;

    /**
     * @return string
     */
    public function getAfterpayAuthTokenExpires(): string;

    /**
     * @param string $redirectCheckoutUrl
     * @return static
     */
    public function setAfterpayRedirectCheckoutUrl(string $redirectCheckoutUrl): self;

    /**
     * @return string
     */
    public function getAfterpayRedirectCheckoutUrl(): string;
}
