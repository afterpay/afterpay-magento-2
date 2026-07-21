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
use Magento\Payment\Gateway\CommandInterface;
use Magento\Store\Model\StoreManagerInterface;

class PlaceOrder implements HttpPostActionInterface
{
    private RequestInterface $request;
    private Session $checkoutSession;
    private JsonFactory $jsonFactory;
    private PlaceOrderProcessor $placeOrderProcessor;
    private CommandInterface $syncCheckoutDataCommand;
    private StoreManagerInterface $storeManager;
    private ManagerInterface $messageManager;
    private \Afterpay\Afterpay\Model\CheckoutManagement\ExpressCheckoutValidator $expressCheckoutValidator;
    private \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager;

    public function __construct(
        RequestInterface      $request,
        Session               $checkoutSession,
        JsonFactory           $jsonFactory,
        PlaceOrderProcessor   $placeOrderProcessor,
        CommandInterface      $syncCheckoutDataCommand,
        StoreManagerInterface $storeManager,
        ManagerInterface      $messageManager,
        \Afterpay\Afterpay\Model\CheckoutManagement\ExpressCheckoutValidator $expressCheckoutValidator,
        \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->syncCheckoutDataCommand = $syncCheckoutDataCommand;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->expressCheckoutValidator = $expressCheckoutValidator;
        $this->attemptManager = $attemptManager;
    }

    public function execute()
    {
        $jsonResult = $this->jsonFactory->create();
        $quote = $this->checkoutSession->getQuote();

        $afterpayOrderToken = $this->request->getParam('orderToken');
        $status = $this->request->getParam('status');
        $attemptId = (string)$this->request->getParam('express_attempt');
        if ($status === Capture::CHECKOUT_STATUS_CANCELLED) {
            $this->attemptManager->revert($attemptId);
            return $jsonResult;
        }

        if ($status !== Capture::CHECKOUT_STATUS_SUCCESS) {
            $this->attemptManager->revert($attemptId);
            $errorMessage = (string)__('Afterpay payment is declined. Please select an alternative payment method.');

            return $jsonResult->setData([
                'error'       => $errorMessage,
                'redirectUrl' => $this->storeManager->getStore()->getUrl('checkout/cart')
            ]);
        }

        try {
            $this->expressCheckoutValidator->validate($quote);
            $quote->getPayment()
                ->setMethod(Config::CODE)
                ->setAdditionalInformation('afterpay_express', true);
            $this->placeOrderProcessor->execute($quote, $this->syncCheckoutDataCommand, $afterpayOrderToken);
        } catch (\Throwable $e) {
            $this->attemptManager->revert($attemptId);
            $errorMessage = $e instanceof LocalizedException
                ? $e->getMessage()
                : (string)__('Afterpay payment is declined. Please select an alternative payment method.');

            $data = [
                'error'       => $errorMessage,
                'redirectUrl' => $this->storeManager->getStore()->getUrl('checkout/cart')
            ];
            if ($e instanceof \Afterpay\Afterpay\Model\CheckoutManagement\RestrictedProductsException) {
                $data['error_code'] = 'restricted_products';
                $data['restricted_skus'] = $e->getRestrictedSkus();
            }

            return $jsonResult->setData($data);
        }

        $this->attemptManager->discard($attemptId);
        $this->messageManager->addSuccessMessage((string)__('Afterpay Transaction Completed.'));

        return $jsonResult->setData([
            'redirectUrl' => $this->storeManager->getStore()->getUrl('checkout/onepage/success')
        ]);
    }
}
