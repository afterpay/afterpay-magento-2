<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Adminhtml\MerchantConfiguration;

class Update extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Payment::payment';

    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    private \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('websiteId');
        if (!$this->isValidWebsiteId($websiteId)) {
            return $this->resultJsonFactory->create()->setHttpResponseCode(400)->setData([
                'done' => false
            ]);
        }
        try {
            $this->merchantConfigurationCommand->execute(
                [
                    "websiteId" => (int)$websiteId
                ]
            );
            $this->messageManager->addSuccessMessage(
                (string)__('Afterpay merchant configuration fetching is success.')
            );
        } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
            $this->messageManager->addWarningMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                (string)__('Afterpay merchant configuration fetching is failed. See logs.')
            );
        }
        return $this->resultJsonFactory->create()->setData([
            'done' => true
        ]);
    }

    private function isValidWebsiteId($websiteId): bool
    {
        if (!is_int($websiteId) && !is_string($websiteId)) {
            return false;
        }
        if (!preg_match('/^(0|[1-9][0-9]*)$/D', (string)$websiteId)
            || filter_var($websiteId, FILTER_VALIDATE_INT) === false
        ) {
            return false;
        }
        if ((int)$websiteId === 0) {
            return true;
        }
        try {
            $this->storeManager->getWebsite((int)$websiteId);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return false;
        }
        return true;
    }
}
