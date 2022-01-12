<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

class RedirectPath implements \Afterpay\Afterpay\Api\Data\RedirectPathInterface
{
    private $confirmPath;
    private $cancelPath;

    public function setConfirmPath(string $path): \Afterpay\Afterpay\Api\Data\RedirectPathInterface
    {
        $this->confirmPath = $path;
        return $this;
    }

    public function getConfirmPath(): string
    {
        return $this->confirmPath;
    }

    public function setCancelPath(string $path): \Afterpay\Afterpay\Api\Data\RedirectPathInterface
    {
        $this->cancelPath = $path;
        return $this;
    }

    public function getCancelPath(): string
    {
        return $this->cancelPath;
    }
}
