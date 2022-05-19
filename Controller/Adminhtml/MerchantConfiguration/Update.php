<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Adminhtml\MerchantConfiguration;

class Update implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    private \Magento\Framework\Message\ManagerInterface $messageManager;
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
    }

    public function execute()
    {
        $websiteId = $this->request->getParam('websiteId');
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
}
