<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Test\Unit\Block\Adminhtml\System\Config\Button;

/**
 * Class UpdateTest
 * @package Afterpay\Afterpay\Test\Unit\Block\Adminhtml\System\Config\Button
 */
class UpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Unit testing on button template file
     */
    public function testToHtml()
    {
        /** @var  $objectManagerHelper */
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        /** @var |Afterpay|Afterpay|Block|Adminhtml|System|Config|Button|Update $updateButton */
        $updateButton = $objectManagerHelper->getObject(
            'Afterpay\Afterpay\Block\Adminhtml\System\Config\Button\Update'
        );

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $objectManagerHelper->getObject(
            'Magento\Framework\View\Layout'
        );

        $updateButton->setLayout($layout);

        // set result and expected
        $result = $updateButton->getTemplate();
        $expected = \Afterpay\Afterpay\Block\Adminhtml\System\Config\Button\Update::UPDATE_TEMPLATE;

        // assert the same
        $this->assertSame($expected, $result);
    }
}