<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2019 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Source;

/**
 * Class ApiMode
 * @package Afterpay\Afterpay\Model\Source
 */
class ApiMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * protected object manager
     */
    protected $objectManager;

    /**
     * ApiMode constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        // get api mode model to get from XML
        $apiMode = $this->objectManager->create('Afterpay\Afterpay\Model\Adapter\ApiMode');

        // looping all data from api modes
        foreach ($apiMode->getAllApiModes() as $name => $environment) {
            array_push(
                $result,
                [
                    'value' => $name,
                    'label' => $environment['label'],
                ]
            );
        }

        // get the result
        return $result;
    }
}
