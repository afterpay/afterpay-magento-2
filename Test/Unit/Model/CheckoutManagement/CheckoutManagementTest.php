<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\CheckoutManagement;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Magento\Authorization\Model\UserContextInterface;

class CheckoutManagementTest extends \PHPUnit\Framework\TestCase
{
    private const CART_OWNER_ID = 7;

    private $checkoutManagement;

    /** @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $maskedQuiteIdToQuoteIdMock;

    /** @var \Magento\Quote\Api\CartRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $cartRepositoryMock;

    /** @var \Magento\Authorization\Model\UserContextInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $userContextMock;

    /** @var \Afterpay\Afterpay\Api\Data\RedirectPathInterface|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $redirectPathStub;

    /** @var \Magento\Quote\Model\Quote|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteStub;

    /** @var \Magento\Quote\Model\Quote\Payment|mixed|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMock;

    /** @var int|null Caller identity used by the user context stub, overridden per test. */
    private $callerUserId = self::CART_OWNER_ID;

    /** @var int|null Caller type used by the user context stub, overridden per test. */
    private $callerUserType = UserContextInterface::USER_TYPE_CUSTOMER;

    protected function setUp(): void
    {
        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->maskedQuiteIdToQuoteIdMock = $this->createMock(
            \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface::class
        );
        $checkoutFactoryStub = $this->createMock(\Afterpay\Afterpay\Api\Data\CheckoutInterfaceFactory::class);

        $this->userContextMock = $this->createMock(\Magento\Authorization\Model\UserContextInterface::class);

        // Resolved lazily so each test can set the caller identity after construction.
        $this->userContextMock->method('getUserId')
            ->willReturnCallback(function () {
                return $this->callerUserId;
            });

        $this->userContextMock->method('getUserType')
            ->willReturnCallback(function () {
                return $this->callerUserType;
            });

        $this->checkoutManagement = new \Afterpay\Afterpay\Model\CheckoutManagement\CheckoutManagement(
            $this->createMock(\Magento\Payment\Gateway\CommandInterface::class),
            $this->createMock(\Magento\Payment\Gateway\CommandInterface::class),
            $this->cartRepositoryMock,
            $this->maskedQuiteIdToQuoteIdMock,
            $checkoutFactoryStub,
            $this->userContextMock
        );

        $checkoutFactoryStub->method('create')
            ->willReturn(new \Afterpay\Afterpay\Model\Checkout());

        $this->redirectPathStub = $this->createMock(\Afterpay\Afterpay\Api\Data\RedirectPathInterface::class);

        $this->quoteStub = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['reserveOrderId', 'getPayment', 'getCustomerId'])
            ->getMock();

        $this->quoteStub->method('reserveOrderId')
            ->willReturnSelf();

        $this->quoteStub->method('getCustomerId')
            ->willReturn(self::CART_OWNER_ID);

        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);

        $this->quoteStub->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->cartRepositoryMock->expects($this->any())
            ->method('save')
            ->willReturn($this->quoteStub);

        $this->cartRepositoryMock
            ->method('getActive')
            ->willReturn($this->quoteStub);
    }

    public function testQuoteMaskedIdConvertedToQuoteId()
    {
        $maskedQuiteId = 'asdad123asdqwe123asd';
        $quoteId = 122;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->willReturn($quoteId);

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create($maskedQuiteId, $this->redirectPathStub);
    }

    public function testQuoteMaskedIdIsAcceptedForGuestCaller()
    {
        $maskedQuiteId = 'asdad123asdqwe123asd';
        $quoteId = 122;

        $this->callerUserId = null;
        $this->callerUserType = UserContextInterface::USER_TYPE_GUEST;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($maskedQuiteId)
            ->willReturn($quoteId);

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with($quoteId)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create($maskedQuiteId, $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsAcceptedForOwningCustomer()
    {
        $quoteId = '322';

        $this->callerUserId = self::CART_OWNER_ID;
        $this->callerUserType = UserContextInterface::USER_TYPE_CUSTOMER;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with((int)$quoteId)
            ->willReturn($this->quoteStub);

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedForAnonymousCaller()
    {
        $quoteId = '322';

        $this->callerUserId = null;
        $this->callerUserType = UserContextInterface::USER_TYPE_GUEST;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage('No such entity with cartId = 322');

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedWhenNoUserContextResolves()
    {
        $quoteId = '322';

        $this->callerUserId = null;
        $this->callerUserType = UserContextInterface::USER_TYPE_CUSTOMER;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage('No such entity with cartId = 322');

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedForIntegrationCaller()
    {
        $quoteId = '322';

        $this->callerUserId = 5;
        $this->callerUserType = UserContextInterface::USER_TYPE_INTEGRATION;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage('No such entity with cartId = 322');

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testNumericQuoteIdIsRefusedForNonOwningCustomer()
    {
        $quoteId = '322';

        $this->callerUserId = self::CART_OWNER_ID + 1;
        $this->callerUserType = UserContextInterface::USER_TYPE_CUSTOMER;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with((int)$quoteId)
            ->willReturn($this->quoteStub);

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage('No such entity with cartId = 322');

        $this->checkoutManagement->create($quoteId, $this->redirectPathStub);
    }

    public function testNonNumericUnmaskableCartIdIsRefused()
    {
        $cartId = 'not-a-real-cart-id';

        $this->callerUserId = self::CART_OWNER_ID;
        $this->callerUserType = UserContextInterface::USER_TYPE_CUSTOMER;

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($cartId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->never())
            ->method('getActive');

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage('No such entity with cartId = not-a-real-cart-id');

        $this->checkoutManagement->create($cartId, $this->redirectPathStub);
    }

    /**
     * Express checkout is session scoped and must keep accepting a numeric quote id for a guest.
     */
    public function testExpressCheckoutAcceptsNumericQuoteIdForGuestCaller()
    {
        $quoteId = '322';

        $this->callerUserId = null;
        $this->callerUserType = UserContextInterface::USER_TYPE_GUEST;

        $this->paymentMock->method('getAdditionalInformation')
            ->willReturn('fake-additional-information');

        $this->maskedQuiteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($quoteId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $this->cartRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with((int)$quoteId)
            ->willReturn($this->quoteStub);

        $checkout = $this->checkoutManagement->createExpress(
            $quoteId,
            'https://test-afterpay.com/checkout/cart'
        );

        static::assertNotNull($checkout);
    }

    public function testCreateCheckout()
    {
        $quoteId = '333';
        $afterpayToken = 'asdasdasdasdsadasd';
        $afterpayAuthTokenExpires = '2021-01-01 00:00:00';
        $afterpayRedirectCheckoutUrl = 'https://test-afterpay.com/redirect?token=123';

        $this->paymentMock->method('getAdditionalInformation')
            ->will(
                $this->returnValueMap([
                    [CheckoutInterface::AFTERPAY_TOKEN, $afterpayToken],
                    [CheckoutInterface::AFTERPAY_AUTH_TOKEN_EXPIRES, $afterpayAuthTokenExpires],
                    [CheckoutInterface::AFTERPAY_REDIRECT_CHECKOUT_URL, $afterpayRedirectCheckoutUrl],
                ])
            );

        $checkout = $this->checkoutManagement->create($quoteId, $this->redirectPathStub);

        static::assertSame($afterpayToken, $checkout->getAfterpayToken());
        static::assertSame($afterpayAuthTokenExpires, $checkout->getAfterpayAuthTokenExpires());
        static::assertSame($afterpayRedirectCheckoutUrl, $checkout->getAfterpayRedirectCheckoutUrl());
    }

    public function testCreateExpressCheckout()
    {
        $quoteId = '333';
        $afterpayToken = 'asdasdasdasdsadasd';
        $afterpayAuthTokenExpires = '2021-01-01 00:00:00';
        $afterpayRedirectCheckoutUrl = 'https://test-afterpay.com/redirect?token=123';

        $testPopupOriginUrl = 'https://test-afterpay.com/checkout/cart';

        $this->paymentMock->method('getAdditionalInformation')
            ->will(
                $this->returnValueMap([
                    [CheckoutInterface::AFTERPAY_TOKEN, $afterpayToken],
                    [CheckoutInterface::AFTERPAY_AUTH_TOKEN_EXPIRES, $afterpayAuthTokenExpires],
                    [CheckoutInterface::AFTERPAY_REDIRECT_CHECKOUT_URL, $afterpayRedirectCheckoutUrl],
                ])
            );

        $checkout = $this->checkoutManagement->createExpress($quoteId, $testPopupOriginUrl);

        static::assertSame($afterpayToken, $checkout->getAfterpayToken());
        static::assertSame($afterpayAuthTokenExpires, $checkout->getAfterpayAuthTokenExpires());
        static::assertSame($afterpayRedirectCheckoutUrl, $checkout->getAfterpayRedirectCheckoutUrl());
    }
}
