<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Observer\Adminhtml;

class ConfigSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    private \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand;
    private \Magento\Framework\Message\ManagerInterface $messageManager;

    public const AFTERPAY_CONFIGS = [
        \Afterpay\Afterpay\Model\Config::XML_PATH_API_MODE,
        \Afterpay\Afterpay\Model\Config::XML_PATH_MERCHANT_KEY,
        \Afterpay\Afterpay\Model\Config::XML_PATH_MERCHANT_ID
    ];
    public const CONFIGS_PATHS_TO_TRACK = [
        \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
        \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY,
        \Afterpay\Afterpay\Model\Config::XML_PATH_PAYPAL_MERCHANT_COUNTRY
    ];

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var array $changedPaths */
        $changedPaths = $observer->getData('changed_paths');
        if (!is_array($changedPaths)) {
            return;
        }
        $isAfterpayConfigChanged = count(array_intersect($changedPaths, self::AFTERPAY_CONFIGS)) > 0;
        if ($isAfterpayConfigChanged || count(array_intersect($changedPaths, self::CONFIGS_PATHS_TO_TRACK)) > 0) {
            $websiteId = $observer->getData('website');
            $store = $observer->getData('store');
            if ($websiteId === '' && $store === '') {
                $websiteId = 0;
            }
            $messageAction = fn () => null;
            if ($websiteId !== '') {
                try {
                    $this->merchantConfigurationCommand->execute([
                        'websiteId' => (int)$observer->getData('website')
                    ]);
                } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
                    $messageAction = fn () => $this->messageManager->addWarningMessage($e->getMessage());
                } catch (\Exception $e) {
                    $messageAction = fn () => $this->messageManager->addErrorMessage(
                        (string)__('Afterpay merchant configuration fetching is failed. See logs.')
                    );
                }
            }
            if ($isAfterpayConfigChanged) {
                $messageAction();
            }
        }
    }
}
