<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Express;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;

class CreateCheckout implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    private $checkoutManagement;
    private $checkoutSession;
    private $url;
    private $jsonResultFactory;
    private $messageManager;
    private $logger;

    public function __construct(
        \Afterpay\Afterpay\Api\CheckoutManagementInterface $checkoutManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->checkoutManagement = $checkoutManagement;
        $this->checkoutSession = $checkoutSession;
        $this->url = $url;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $result = $this->jsonResultFactory->create();
        try {
            $checkout = $this->checkoutManagement->createExpress(
                (string)$this->checkoutSession->getQuoteId(),
                $this->url->getUrl('checkout/cart')
            );
            $result->setData([
                CheckoutInterface::AFTERPAY_TOKEN => $checkout->getAfterpayToken()
            ]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage((string)__('Afterpay payment is declined. Please select an alternative payment method.'));
        }
        return $result;
    }
}
