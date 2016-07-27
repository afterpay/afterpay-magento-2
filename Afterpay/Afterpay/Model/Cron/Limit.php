<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model\Cron;

use Afterpay\Afterpay\Model\Adapter\AfterpayTotalLimit;

class Limit
{
    /**
     * @var AfterpayTotalLimit
     */
    protected $afterpayTotalLimit;
    protected $jsonHelper;
    protected $resourceConfig;

    /**
     * Limit constructor.
     * @param AfterpayTotalLimit $afterpayTotalLimit
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     */
    public function __construct(
        AfterpayTotalLimit $afterpayTotalLimit,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        $this->afterpayTotalLimit = $afterpayTotalLimit;
        $this->jsonHelper = $jsonHelper;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        $response = $this->afterpayTotalLimit->getLimit();
        $response = $this->jsonHelper->jsonDecode($response->getBody());

        // default min and max if not provided
        $minTotal = "0";
        $maxTotal = "0";

        // understand the response from the API
        foreach ($response as $result) {
            if ($result['type'] === \Afterpay\Afterpay\Model\Payovertime::AFTERPAY_PAYMENT_TYPE_CODE) {
                $minTotal = isset($result['minimumAmount']['amount']) ? $result['minimumAmount']['amount'] : "0";
                $maxTotal = isset($result['maximumAmount']['amount']) ? $result['maximumAmount']['amount'] : "0";
            }
        }

        $this->resourceConfig->saveConfig(
            'payment/' . \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '/' . \Afterpay\Afterpay\Model\Config\Payovertime::MIN_TOTAL_LIMIT,
            $minTotal,
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/' . \Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '/' . \Afterpay\Afterpay\Model\Config\Payovertime::MAX_TOTAL_LIMIT,
            $maxTotal,
            'default',
            0
        );

        return true;

    }
}