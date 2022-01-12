<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Api;

/**
 * Interface for managing Afterpay Checkout
 * @api
 */
interface CheckoutManagementInterface
{
    /**
     * @param string $cartId
     * @param \Afterpay\Afterpay\Api\Data\RedirectPathInterface $redirectPath
     *
     * @return \Afterpay\Afterpay\Api\Data\CheckoutInterface
     *
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function create(
        string $cartId,
        \Afterpay\Afterpay\Api\Data\RedirectPathInterface $redirectPath
    ): \Afterpay\Afterpay\Api\Data\CheckoutInterface;

    /**
     * @param string $cartId
     * @param string $popupOriginUrl
     *
     * @return \Afterpay\Afterpay\Api\Data\CheckoutInterface
     *
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function createExpress(
        string $cartId,
        string $popupOriginUrl
    ): \Afterpay\Afterpay\Api\Data\CheckoutInterface;
}
