<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\CheckoutManagement;

class ExpressCheckoutValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testRestrictedProductsAreRejectedWithSkus(): void
    {
        $config = $this->createMock(\Afterpay\Afterpay\Model\Config::class);
        $provider = $this->createMock(
            \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider::class
        );
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->addMethods(['getProductId'])
            ->onlyMethods(['getSku'])
            ->disableOriginalConstructor()
            ->getMock();

        $quote->method('getStoreId')->willReturn(1);
        $quote->method('getAllItems')->willReturn([$item]);
        $provider->expects($this->once())
            ->method('getRestrictedSkusFromQuote')
            ->with($quote)
            ->willReturn(['RESTRICTED-SKU']);
        $item->method('getProductId')->willReturn(42);
        $item->method('getSku')->willReturn('RESTRICTED-SKU');

        $validator = new \Afterpay\Afterpay\Model\CheckoutManagement\ExpressCheckoutValidator(
            $config,
            $provider
        );

        try {
            $validator->validate($quote);
            self::fail('Expected restricted products validation to fail.');
        } catch (\Afterpay\Afterpay\Model\CheckoutManagement\RestrictedProductsException $exception) {
            self::assertSame(['RESTRICTED-SKU'], $exception->getRestrictedSkus());
            self::assertStringContainsString('RESTRICTED-SKU', $exception->getMessage());
        }
    }

    public function testAllowedProductsWithinOrderLimitsPass(): void
    {
        $config = $this->createMock(\Afterpay\Afterpay\Model\Config::class);
        $provider = $this->createMock(
            \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider::class
        );
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->addMethods(['getBaseGrandTotal'])
            ->onlyMethods(['getStoreId', 'getAllItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $quote->method('getStoreId')->willReturn(1);
        $quote->method('getAllItems')->willReturn([]);
        $quote->method('getBaseGrandTotal')->willReturn(100.0);
        $provider->method('getRestrictedSkusFromQuote')->with($quote)->willReturn([]);
        $config->method('getMinOrderTotal')->willReturn('1.0');
        $config->method('getMaxOrderTotal')->willReturn('1000.0');

        $validator = new \Afterpay\Afterpay\Model\CheckoutManagement\ExpressCheckoutValidator(
            $config,
            $provider
        );
        $validator->validate($quote);

        self::assertTrue(true);
    }
}
