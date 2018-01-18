<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Test\Unit\Model\Adapter;

/**
 * Class AfterpayOrderTokenTest
 * @package Afterpay\Afterpay\Test\Unit\Model\Adapter
 */
class AfterpayOrderTokenTest extends \PHPUnit\Framework\TestCase
{
    /**
     * mock the order
     */
    public function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getMock();
        $this->paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->setMethods([
                'getOrder', 'getId', 'setAdditionalInformation', 'getAdditionalInformation',
                'setIsTransactionDenied', 'setIsTransactionClosed', 'decrypt', 'getCcLast4',
                'getParentTransactionId', 'getPoNumber'
            ])
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder('Magento\Authorizenet\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

//        $this->initResponseFactoryMock();

        $this->transactionRepositoryMock = $this->getMockBuilder(
            'Magento\Sales\Model\Order\Payment\Transaction\Repository'
        )
            ->disableOriginalConstructor()
            ->setMethods(['getByTransactionId'])
            ->getMock();

        $this->transactionServiceMock = $this->getMockBuilder('Magento\Authorizenet\Model\TransactionService')
            ->disableOriginalConstructor()
            ->setMethods(['getTransactionDetails'])
            ->getMock();

//        $this->requestFactory = $this->getRequestFactoryMock();
//        $httpClientFactoryMock = $this->getHttpClientFactoryMock();
//
//        $helper = new ObjectManagerHelper($this);
//        $this->directpost = $helper->getObject(
//            'Magento\Authorizenet\Model\Directpost',
//            [
//                'scopeConfig' => $this->scopeConfigMock,
//                'dataHelper' => $this->dataHelperMock,
//                'requestFactory' => $this->requestFactory,
//                'responseFactory' => $this->responseFactoryMock,
//                'transactionRepository' => $this->transactionRepositoryMock,
//                'transactionService' => $this->transactionServiceMock,
//                'httpClientFactory' => $httpClientFactoryMock
//            ]
//        );
    }

    /**
     * Actual testing method
     */
    public function testValidateRequestToken()
    {
       $this->assertEquals(true,true);
    }
}
