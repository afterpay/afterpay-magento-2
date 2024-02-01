<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\ApiFunctional\GraphQl;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;

class CreateAfterpayCheckoutTest extends \Magento\TestFramework\TestCase\GraphQlAbstract
{
    /**
     * @var \Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId|mixed
     */
    private $getMaskedQuoteIdByReservedOrderId;

    protected function setUp(): void
    {
        $this->getMaskedQuoteIdByReservedOrderId = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get(\Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/guest_quote_with_addresses.php
     * @magentoConfigFixture default/payment/afterpay/active 1
     */
    public function testCreateAfterpayCheckoutReturnData()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('guest_quote');

        $mutation = $this->createAfterpayCheckoutMutation($maskedQuoteId);
        $response = $this->graphQlMutation($mutation);

        self::assertArrayHasKey(CheckoutInterface::AFTERPAY_TOKEN, $response['createAfterpayCheckout']);
        self::assertArrayHasKey(CheckoutInterface::AFTERPAY_AUTH_TOKEN_EXPIRES, $response['createAfterpayCheckout']);
        self::assertArrayHasKey(CheckoutInterface::AFTERPAY_REDIRECT_CHECKOUT_URL, $response['createAfterpayCheckout']);
    }

    public function testNoSuchCartException()
    {
        $emptyMaskedCartId = '';
        $mutation = $this->createAfterpayCheckoutMutation($emptyMaskedCartId);

        self::expectExceptionMessageMatches('/No such entity.*/');
        $this->graphQlMutation($mutation);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     * @magentoConfigFixture default/payment/afterpay/active 0
     */
    public function testPaymentIsNotActiveException()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_order_1');
        $mutation = $this->createAfterpayCheckoutMutation($maskedQuoteId);

        self::expectExceptionMessage('Afterpay payment method is not active');
        $this->graphQlMutation($mutation);
    }

    private function createAfterpayCheckoutMutation(string $maskedCartId): string
    {
        return <<<QUERY
mutation {
    createAfterpayCheckout(input: {
        cart_id: "{$maskedCartId}"
        redirect_path: {
            cancel_path: "frontend/cancel/path"
            confirm_path: "frontend/confirm/path"
        }
    }) {
        afterpay_token
        afterpay_expires
        afterpay_redirectCheckoutUrl
    }
}
QUERY;
    }
}
