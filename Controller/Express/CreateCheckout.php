<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Express;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;

class CreateCheckout implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    private \Afterpay\Afterpay\Api\CheckoutManagementInterface $checkoutManagement;
    private \Magento\Checkout\Model\Session $checkoutSession;
    private \Magento\Framework\UrlInterface $url;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;
    private \Psr\Log\LoggerInterface $logger;
    private \Magento\Framework\App\RequestInterface $request;
    private \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager;

    public function __construct(
        \Afterpay\Afterpay\Api\CheckoutManagementInterface $checkoutManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager
    ) {
        $this->checkoutManagement = $checkoutManagement;
        $this->checkoutSession = $checkoutSession;
        $this->url = $url;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
        $this->request = $request;
        $this->attemptManager = $attemptManager;
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $result = $this->jsonResultFactory->create();
        try {
            $attemptId = (string)$this->request->getParam('express_attempt');
            if ($attemptId !== '' && !$this->attemptManager->has($attemptId)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The product could not be added for Afterpay Express Checkout. Please try again.')
                );
            }
            $checkout = $this->checkoutManagement->createExpress(
                (string)$this->checkoutSession->getQuoteId(),
                $this->url->getUrl('checkout/cart')
            );
            $result->setData([
                CheckoutInterface::AFTERPAY_TOKEN => $checkout->getAfterpayToken()
            ]);
        } catch (\Afterpay\Afterpay\Model\CheckoutManagement\RestrictedProductsException $e) {
            $this->revertPdpAttempt();
            $result->setData([
                'success' => false,
                'error_code' => 'restricted_products',
                'message' => $e->getMessage(),
                'restricted_skus' => $e->getRestrictedSkus(),
            ]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->revertPdpAttempt();
            $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->revertPdpAttempt();
            $this->logger->error($e->getMessage());
            $message = (string)__('Afterpay payment is declined. Please select an alternative payment method.');
            $result->setData(['success' => false, 'message' => $message]);
        }
        return $result;
    }

    private function revertPdpAttempt(): void
    {
        $attemptId = (string)$this->request->getParam('express_attempt');
        if ($attemptId !== '') {
            $this->attemptManager->revert($attemptId);
        }
    }
}
