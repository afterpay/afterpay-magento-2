<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

class Checkout implements \Afterpay\Afterpay\Api\Data\CheckoutInterface
{
    private string $afterpayToken;
    private string $afterpayAuthTokenExpires;
    private string $afterpayRedirectCheckoutUrl;

    public function setAfterpayToken(string $token): self
    {
        $this->afterpayToken = $token;
        return $this;
    }

    public function getAfterpayToken(): string
    {
        return $this->afterpayToken;
    }

    public function setAfterpayAuthTokenExpires(string $authTokenExpires): self
    {
        $this->afterpayAuthTokenExpires = $authTokenExpires;
        return $this;
    }

    public function getAfterpayAuthTokenExpires(): string
    {
        return $this->afterpayAuthTokenExpires;
    }

    public function setAfterpayRedirectCheckoutUrl(string $redirectCheckoutUrl): self
    {
        $this->afterpayRedirectCheckoutUrl = $redirectCheckoutUrl;
        return $this;
    }

    public function getAfterpayRedirectCheckoutUrl(): string
    {
        return $this->afterpayRedirectCheckoutUrl;
    }
}
