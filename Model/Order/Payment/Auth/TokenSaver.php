<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\Payment\Auth;

use Afterpay\Afterpay\Model\ResourceModel\Token;
use Magento\Framework\Stdlib\DateTime;

class TokenSaver
{
    private $tokensResource;
    private $dateTime;

    public function __construct(
        Token    $tokensResource,
        DateTime $dateTime
    ) {
        $this->tokensResource = $tokensResource;
        $this->dateTime = $dateTime;
    }

    public function execute(int $orderId, string $token, ?string $expiryDate): bool
    {
        return (bool)$this->tokensResource->insertNewToken($orderId, $token, $this->dateTime->formatDate($expiryDate));
    }
}
