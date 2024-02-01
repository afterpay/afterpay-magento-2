<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Order\Payment\Auth;

class ExpiryDate
{
    public const FORMAT = 'Y-m-d H:i T';

    private $timezone;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    public function format(?string $date = null): string
    {
        return $this->timezone->date($date)->format(static::FORMAT);
    }

    public function isExpired(string $expireDate, ?string $dateToCheck = null): bool
    {
        if ($dateToCheck == null) {
            $dateToCheck = $this->format();
        }
        return strtotime($expireDate) < strtotime($dateToCheck);
    }
}
