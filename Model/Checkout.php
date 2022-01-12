<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;

class Checkout implements CheckoutInterface
{
    private $afterpayToken;
    private $afterpayAuthTokenExpires;
    private $afterpayRedirectCheckoutUrl;

    public function setAfterpayToken(string $token): CheckoutInterface
    {
        $this->afterpayToken = $token;
        return $this;
    }

    public function getAfterpayToken(): string
    {
        return $this->afterpayToken;
    }

    public function setAfterpayAuthTokenExpires(string $authTokenExpires): CheckoutInterface
    {
        $this->afterpayAuthTokenExpires = $authTokenExpires;
        return $this;
    }

    public function getAfterpayAuthTokenExpires(): string
    {
        return $this->afterpayAuthTokenExpires;
    }

    public function setAfterpayRedirectCheckoutUrl(string $redirectCheckoutUrl): CheckoutInterface
    {
        $this->afterpayRedirectCheckoutUrl = $redirectCheckoutUrl;
        return $this;
    }

    public function getAfterpayRedirectCheckoutUrl(): string
    {
        return $this->afterpayRedirectCheckoutUrl;
    }
}
