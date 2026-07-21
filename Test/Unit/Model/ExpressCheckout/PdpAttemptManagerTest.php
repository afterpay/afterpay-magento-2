<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Test\Unit\Model\ExpressCheckout;

class PdpAttemptManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testRevertRestoresQuantityThatExistedBeforeExpressCheckout(): void
    {
        $session = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->addMethods(['setData'])
            ->onlyMethods(['getData', 'getQuote', 'replaceQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryFactory = $this->createMock(
            \Magento\Quote\Api\CartRepositoryInterfaceFactory::class
        );
        $repository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $resourceConnection = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $item = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $sessionData = [];

        $session->method('getData')->willReturnCallback(
            static function (string $key) use (&$sessionData) {
                return $sessionData[$key] ?? null;
            }
        );
        $session->method('setData')->willReturnCallback(
            static function (string $key, $value) use (&$sessionData, $session) {
                $sessionData[$key] = $value;
                return $session;
            }
        );
        $session->method('getQuote')->willReturn($quote);
        $session->expects($this->once())->method('replaceQuote')->with($quote);
        $repositoryFactory->method('create')->willReturn($repository);
        $repository->method('getActive')->with(10)->willReturn($quote);
        $resourceConnection->method('getConnection')->willReturn($connection);
        $resourceConnection->method('getTableName')->with('quote')->willReturn('quote');
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->with($select)->willReturn('10');
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('forUpdate')->willReturnSelf();
        $quote->method('getId')->willReturn(10);
        $quote->method('getAllVisibleItems')->willReturn([$item]);
        $quote->method('getItemById')->with(7)->willReturn($item);
        $quote->method('collectTotals')->willReturnSelf();
        $item->method('getId')->willReturn(7);
        $item->method('getQty')->willReturnOnConsecutiveCalls(2.0, 3.0, 3.0, 2.0);
        $item->expects($this->once())->method('setQty')->with(2.0);
        $repository->expects($this->once())->method('save')->with($quote);

        $manager = new \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager(
            $session,
            $repositoryFactory,
            $resourceConnection
        );
        $before = $manager->snapshot($quote);
        $manager->record('ap-test', $quote, $before);

        self::assertTrue($manager->revert('ap-test'));
        self::assertFalse($manager->revert('ap-test'));
    }

    public function testRevertRemovesNewItemFromInitiallyEmptyCart(): void
    {
        $session = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->addMethods(['setData'])
            ->onlyMethods(['getData', 'getQuote', 'replaceQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryFactory = $this->createMock(
            \Magento\Quote\Api\CartRepositoryInterfaceFactory::class
        );
        $repository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $resourceConnection = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $emptyQuote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $persistedQuote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $item = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $sessionData = [];

        $session->method('getData')->willReturnCallback(
            static function (string $key) use (&$sessionData) {
                return $sessionData[$key] ?? null;
            }
        );
        $session->method('setData')->willReturnCallback(
            static function (string $key, $value) use (&$sessionData, $session) {
                $sessionData[$key] = $value;
                return $session;
            }
        );
        $session->method('getQuote')->willReturn($persistedQuote);
        $session->expects($this->once())->method('replaceQuote')->with($persistedQuote);
        $repositoryFactory->method('create')->willReturn($repository);
        $repository->method('getActive')->with(10)->willReturn($persistedQuote);
        $resourceConnection->method('getConnection')->willReturn($connection);
        $resourceConnection->method('getTableName')->with('quote')->willReturn('quote');
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->with($select)->willReturn('10');
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('forUpdate')->willReturnSelf();
        $emptyQuote->method('getAllVisibleItems')->willReturn([]);
        $persistedQuote->method('getId')->willReturn(10);
        $persistedQuote->method('getAllVisibleItems')->willReturn([$item]);
        $persistedQuote->method('getItemById')->with(7)->willReturnOnConsecutiveCalls($item, null);
        $persistedQuote->method('collectTotals')->willReturnSelf();
        $persistedQuote->expects($this->once())->method('removeItem')->with(7);
        $item->method('getId')->willReturn(7);
        $item->method('getQty')->willReturn(1.0);

        $manager = new \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager(
            $session,
            $repositoryFactory,
            $resourceConnection
        );
        $manager->record('ap-empty', $persistedQuote, $manager->snapshot($emptyQuote));

        self::assertTrue($manager->has('ap-empty'));
        self::assertTrue($manager->revert('ap-empty'));
    }
}
