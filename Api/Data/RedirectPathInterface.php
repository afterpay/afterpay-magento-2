<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Api\Data;

/**
 * Interface RedirectPathInterface
 * @api
 */
interface RedirectPathInterface
{
    /**
     * @param string $path
     * @return static
     */
    public function setConfirmPath(string $path): self;

    /**
     * @return string
     */
    public function getConfirmPath(): string;

    /**
     * @param string $path
     * @return static
     */
    public function setCancelPath(string $path): self;

    /**
     * @return string
     */
    public function getCancelPath(): string;
}
