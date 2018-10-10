<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 * Updated on 19th April 2018
 * Removed API V0 assertions
 */
namespace Afterpay\Afterpay\Test\Unit\Block\Adminhtml\System\Config\Button;
use \PHPUnit\Framework\TestCase;

/**
 * Class UpdateTest
 * Includes Sample assertions
 */
class UpdateTest extends TestCase
{
    //Sample assertions

    //Additions of 2 numbers
    public function testAdd()
    {
        $a = 7;
        $b = 5;
        $expected = 12;
        $this->assertEquals($expected, $a + $b);
    }

    //Stack Push and Pop
    public function testPushAndPop()
    {
        $stack = [];
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
  
}