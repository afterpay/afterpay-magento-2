<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Payment;

use Afterpay\Afterpay\Model\Payment\Capture\PlaceOrderProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Psr\Log\LoggerInterface;

class Capture implements HttpGetActionInterface
{
    public const CHECKOUT_STATUS_CANCELLED = 'CANCELLED';
    public const CHECKOUT_STATUS_SUCCESS = 'SUCCESS';
    private RequestInterface $request;
    private Session $session;
    private RedirectFactory $redirectFactory;
    private ManagerInterface $messageManager;
    private PlaceOrderProcessor $placeOrderProcessor;
    private CommandInterface $validateCheckoutDataCommand;
    private LoggerInterface $logger;

    public function __construct(
        RequestInterface    $request,
        Session             $session,
        RedirectFactory     $redirectFactory,
        ManagerInterface    $messageManager,
        PlaceOrderProcessor $placeOrderProcessor,
        CommandInterface    $validateCheckoutDataCommand,
        LoggerInterface     $logger
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->validateCheckoutDataCommand = $validateCheckoutDataCommand;
        $this->logger = $logger;
    }

    public function execute()
    {
        $afterpayOrderToken = $this->request->getParam('orderToken');
        if ($this->request->getParam('status') == self::CHECKOUT_STATUS_CANCELLED) {
            $this->messageManager->addErrorMessage(
                (string)__('You have cancelled your Afterpay payment. Please select an alternative payment method.')
            );

            return $this->redirectFactory->create()->setPath('checkout/cart');
        }
        if ($this->request->getParam('status') != self::CHECKOUT_STATUS_SUCCESS) {
            $this->messageManager->addErrorMessage(
                (string)__('Afterpay payment is declined. Please select an alternative payment method.')
            );
            $this->logger->info(
                'Afterpay payment(' . $afterpayOrderToken . ') response status is "' . $this->request->getParam('status')
                . '".' . 'Customer has been redirected to the cart page.'
            );

            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        try {
            $quote = $this->session->getQuote();
            $this->placeOrderProcessor->execute($quote, $this->validateCheckoutDataCommand, $afterpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof LocalizedException
                ? $e->getMessage()
                : (string)__('Afterpay payment is declined. Please select an alternative payment method.');
            $this->messageManager->addErrorMessage($errorMessage);

            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $this->messageManager->addSuccessMessage((string)__('Afterpay Transaction Completed.'));

        return $this->redirectFactory->create()->setPath('checkout/onepage/success');
    }
}
