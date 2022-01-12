<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Payment;

class Capture implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    const CHECKOUT_STATUS_CANCELLED = 'CANCELLED';
    const CHECKOUT_STATUS_SUCCESS = 'SUCCESS';

    private $request;
    private $session;
    private $redirectFactory;
    private $messageManager;
    private $placeOrderProcessor;
    private $validateCheckoutDataCommand;
    private $storeManager;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Afterpay\Afterpay\Model\Payment\Capture\PlaceOrderProcessor $placeOrderProcessor,
        \Magento\Payment\Gateway\CommandInterface $validateCheckoutDataCommand,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->validateCheckoutDataCommand = $validateCheckoutDataCommand;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        if ($this->request->getParam('status') == self::CHECKOUT_STATUS_CANCELLED) {
            $this->messageManager->addErrorMessage(
                (string)__('You have cancelled your Afterpay payment. Please select an alternative payment method.')
            );
            return $this->redirectFactory->create()->setPath('checkout/cart', [
                '_scope' => $this->storeManager->getStore()
            ]);
        }
        if ($this->request->getParam('status') != self::CHECKOUT_STATUS_SUCCESS) {
            $this->messageManager->addErrorMessage(
                (string)__('Afterpay payment is failed. Please select an alternative payment method.')
            );
            return $this->redirectFactory->create()->setPath('checkout/cart', [
                '_scope' => $this->storeManager->getStore()
            ]);
        }

        try {
            $quote = $this->session->getQuote();
            $afterpayOrderToken = $this->request->getParam('orderToken');
            $this->placeOrderProcessor->execute($quote, $this->validateCheckoutDataCommand, $afterpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof \Magento\Framework\Exception\LocalizedException
                ? $e->getMessage()
                : (string)__('Payment is failed');
            $this->messageManager->addErrorMessage($errorMessage);
            return $this->redirectFactory->create()->setPath('checkout/cart', [
                '_scope' => $this->storeManager->getStore()
            ]);
        }

        $this->messageManager->addSuccessMessage((string)__('Afterpay Transaction Completed'));
        return $this->redirectFactory->create()->setPath('checkout/onepage/success');
    }
}
