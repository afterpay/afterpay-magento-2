<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Config\Save;

/**
 * Class Plugin
 * @package Afterpay\Afterpay\Model\Config\Save
 */
class Plugin
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;
    protected $afterpayTotalLimit;
    protected $resourceConfig;
    protected $requested;
    protected $storeManager;
    protected $request;
    protected $messageManager;

    /**
     * Plugin constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Afterpay\Afterpay\Model\Adapter\AfterpayTotalLimit $afterpayTotalLimit
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Afterpay\Afterpay\Model\Adapter\AfterpayTotalLimit $afterpayTotalLimit,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->afterpayTotalLimit = $afterpayTotalLimit;
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure $proceed
     */
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        
        //first saving run to eliminate possibilities of conflicting config results
        $proceed();

        if (class_exists('\Afterpay\Afterpay\Model\Payovertime')) {
            $configRequest = $subject->getGroups();
            $this->requested = array_key_exists(\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE, $configRequest);

            if ($this->requested) {
                $response = $this->afterpayTotalLimit->getLimit();
                $response = $this->jsonHelper->jsonDecode($response->getBody());

                if (!array_key_exists('errorCode', $response)) {
                    // default min and max if not provided
                    $minTotal = "0";
                    $maxTotal = "0";

                    // understand the response from the API
                    foreach ($response as $result) {
                        if (!empty($result['type']) && $result['type'] === \Afterpay\Afterpay\Model\Payovertime::AFTERPAY_PAYMENT_TYPE_CODE_V1) {
                            $minTotal = isset($result['minimumAmount']['amount']) ? $result['minimumAmount']['amount'] : "0";
                            $maxTotal = isset($result['maximumAmount']['amount']) ? $result['maximumAmount']['amount'] : "0";
                        }
                    }

                    //Change the minimum amd maximum to Not applicable if both limits are 0.
                    if ($minTotal == "0" && $maxTotal=="0") {
                        $minTotal="N/A";
                        $maxTotal="N/A";
                    }

                    // set on config request
                    $configRequest[\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE]['groups'][\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '_advanced']['fields'][\Afterpay\Afterpay\Model\Config\Payovertime::MIN_TOTAL_LIMIT]['value'] = $minTotal;
                    $configRequest[\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE]['groups'][\Afterpay\Afterpay\Model\Payovertime::METHOD_CODE . '_advanced']['fields'][\Afterpay\Afterpay\Model\Config\Payovertime::MAX_TOTAL_LIMIT]['value'] = $maxTotal;

                    $subject->setGroups($configRequest);

                    return $proceed();
                } else {
                    $this->messageManager->addWarningMessage('Afterpay Update Limits Failed. Please check Merchant ID and Key.');
                }
            }
        }

        return true;
    }
}
