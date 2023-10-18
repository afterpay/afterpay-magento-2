<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\Payment\Auth;

use Afterpay\Afterpay\Model\ResourceModel\Token;

class TokenValidator
{
    private $results = [];
    private $tokensResource;

    public function __construct(Token $tokensResource)
    {
        $this->tokensResource = $tokensResource;
    }

    public function checkIsUsed(string $token): bool
    {
        if (!isset($this->results[$token])) {
            $this->results[$token] = (bool)$this->tokensResource->selectByToken($token);
        }

        return $this->results[$token];
    }
}
