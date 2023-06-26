<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Express;

use Afterpay\Afterpay\Controller\Payment\Capture;
use Afterpay\Afterpay\Gateway\Config\Config;
use Afterpay\Afterpay\Model\Payment\Capture\PlaceOrderProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;

class PlaceOrder implements HttpPostActionInterface
{
    private $request;
    private $messageManager;
    private $checkoutSession;
    private $jsonFactory;
    private $url;
    private $placeOrderProcessor;
    private $syncCheckoutDataCommand;

    public function __construct(
        RequestInterface    $request,
        ManagerInterface    $messageManager,
        Session             $checkoutSession,
        JsonFactory         $jsonFactory,
        UrlInterface        $url,
        PlaceOrderProcessor $placeOrderProcessor,
        CommandInterface    $syncCheckoutDataCommand
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
        $this->url = $url;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->syncCheckoutDataCommand = $syncCheckoutDataCommand;
    }

    public function execute()
    {
        $jsonResult = $this->jsonFactory->create();
        $quote = $this->checkoutSession->getQuote();

        $afterpayOrderToken = $this->request->getParam('orderToken');
        $status = $this->request->getParam('status');

        if ($status === Capture::CHECKOUT_STATUS_CANCELLED) {
            return $jsonResult;
        }

        if ($status !== Capture::CHECKOUT_STATUS_SUCCESS) {
            $errorMessage = (string)__('Afterpay payment is declined. Please select an alternative payment method.');
            $this->messageManager->addErrorMessage($errorMessage);

            return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/cart')]);
        }

        try {
            $quote->getPayment()
                ->setMethod(Config::CODE)
                ->setAdditionalInformation('afterpay_express', true);
            $this->placeOrderProcessor->execute($quote, $this->syncCheckoutDataCommand, $afterpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof LocalizedException
                ? $e->getMessage()
                : (string)__('Afterpay payment is declined. Please select an alternative payment method.');
            $this->messageManager->addErrorMessage($errorMessage);

            return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/cart')]);
        }

        return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/onepage/success')]);
    }
}
