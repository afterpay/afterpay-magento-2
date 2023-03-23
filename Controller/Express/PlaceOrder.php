<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Express;

class PlaceOrder implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    const CANCELLED_STATUS = 'CANCELLED';

    private $request;
    private $messageManager;
    private $checkoutSession;
    private $jsonFactory;
    private $url;
    private $placeOrderProcessor;
    private $syncCheckoutDataCommand;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\UrlInterface $url,
        \Afterpay\Afterpay\Model\Payment\Capture\PlaceOrderProcessor $placeOrderProcessor,
        \Magento\Payment\Gateway\CommandInterface $syncCheckoutDataCommand
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

        if ($status === static::CANCELLED_STATUS) {
            return $jsonResult;
        }

        try {
            $quote->getPayment()
                ->setMethod(\Afterpay\Afterpay\Gateway\Config\Config::CODE)
                ->setAdditionalInformation('afterpay_express', true);
            $this->placeOrderProcessor->execute($quote, $this->syncCheckoutDataCommand, $afterpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof \Magento\Framework\Exception\LocalizedException
                ? $e->getMessage()
                : (string)__('Afterpay payment declined. Please select an alternative payment method.');

            return $jsonResult->setData(['error' => $errorMessage, 'redirectUrl' => $this->url->getUrl(
                    'checkout/cart',
                    ['_scope' => $quote->getStore()]
                )]
            );
        }

        return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/onepage/success')]);
    }
}
