<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Express;

class GetShippingOptions implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    private $checkoutSession;
    private $jsonResultFactory;
    private $request;
    private $shippingListProvider;
    private $shippingAddressUpdater;
    private $logger;
    private $messageManager;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Afterpay\Afterpay\Model\Shipment\Express\ShippingAddressUpdater $shippingAddressUpdater,
        \Afterpay\Afterpay\Model\Shipment\Express\ShippingListProvider $shippingListProvider,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->shippingAddressUpdater = $shippingAddressUpdater;
        $this->shippingListProvider = $shippingListProvider;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        $shippingAddress = $this->request->getParams();
        $shippingList = [];
        try {
            $quote = $this->checkoutSession->getQuote();
            $quote = $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
            $shippingList = $this->shippingListProvider->provide($quote);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
        if (empty($shippingList)) {
            $this->messageManager->addErrorMessage(
                (string)__('Shipping is unavailable for this address, or all options exceed Afterpay order limit.')
            );
        }
        return $this->jsonResultFactory->create()
            ->setData($this->getResult($shippingList));
    }

    private function getResult(array $shippingList): array
    {
        if (!empty($shippingList)) {
            return [
                'success' => true,
                'shippingOptions' => $shippingList
            ];
        }
        return [
            'error' => true,
        ];
    }
}
