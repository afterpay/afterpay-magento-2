<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\GraphQl\Resolver;

use Afterpay\Afterpay\Api\CheckoutManagementInterface;
use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Api\Data\RedirectPathInterfaceFactory;
use Afterpay\Afterpay\Model\Config;
use GraphQL\Error\Error;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class CreateAfterpayCheckout implements ResolverInterface
{
    private Config $config;
    private CheckoutManagementInterface $afterpayCheckoutManagement;
    private RedirectPathInterfaceFactory $redirectPathFactory;

    public function __construct(
        Config                       $config,
        CheckoutManagementInterface  $afterpayCheckoutManagement,
        RedirectPathInterfaceFactory $redirectPathFactory
    ) {
        $this->config = $config;
        $this->afterpayCheckoutManagement = $afterpayCheckoutManagement;
        $this->redirectPathFactory = $redirectPathFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        try {
            /** @phpstan-ignore-next-line */
            $websiteId = $context->getExtensionAttributes()->getStore()->getWebsiteId();

            if (!$this->config->getIsPaymentActive((int)$websiteId)) {
                throw new GraphQlInputException(__('Afterpay payment method is not active'));
            }

            if (!$args || !$args['input']) {
                throw new \InvalidArgumentException('Required params cart_id and redirect_path are missing');
            }

            $maskedCartId = $args['input']['cart_id'];
            $afterpayRedirectPath = $args['input']['redirect_path'];

            $redirectUrls = $this->redirectPathFactory->create()
                ->setConfirmPath($afterpayRedirectPath['confirm_path'])
                ->setCancelPath($afterpayRedirectPath['cancel_path']);

            $checkoutResult = $this->afterpayCheckoutManagement->create($maskedCartId, $redirectUrls);

            return [
                CheckoutInterface::AFTERPAY_TOKEN                 => $checkoutResult->getAfterpayToken(),
                CheckoutInterface::AFTERPAY_AUTH_TOKEN_EXPIRES    => $checkoutResult->getAfterpayAuthTokenExpires(),
                CheckoutInterface::AFTERPAY_REDIRECT_CHECKOUT_URL => $checkoutResult->getAfterpayRedirectCheckoutUrl()
            ];
        } catch (LocalizedException $exception) {
            throw new Error($exception->getMessage());
        }
    }
}
