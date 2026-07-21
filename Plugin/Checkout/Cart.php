<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Plugin\Checkout;

class Cart
{
    private \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager;
    private \Magento\Checkout\Model\Session $checkoutSession;
    private string $attemptId = '';
    private array $before = [];

    public function __construct(
        \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->attemptManager = $attemptManager;
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundAddProduct(
        \Magento\Checkout\Model\Cart $subject,
        callable $proceed,
        $productInfo,
        $requestInfo = null
    ) {
        $attemptId = '';
        if ($requestInfo instanceof \Magento\Framework\DataObject) {
            $attemptId = (string)$requestInfo->getData('afterpay_express_attempt');
        } elseif (is_array($requestInfo)) {
            $attemptId = (string)($requestInfo['afterpay_express_attempt'] ?? '');
        }
        if ($attemptId === '') {
            return $proceed($productInfo, $requestInfo);
        }

        $isHeadless = $requestInfo instanceof \Magento\Framework\DataObject
            ? (bool)$requestInfo->getData('afterpay_express_headless')
            : (bool)($requestInfo['afterpay_express_headless'] ?? false);
        $this->attemptId = $attemptId;
        $this->before = $this->attemptManager->snapshot($subject->getQuote());
        if ($isHeadless) {
            $this->checkoutSession->setNoCartRedirect(true);
        }

        try {
            return $proceed($productInfo, $requestInfo);
        } catch (\Throwable $exception) {
            $this->attemptId = '';
            $this->before = [];
            if ($isHeadless) {
                $this->checkoutSession->unsNoCartRedirect();
            }
            throw $exception;
        }
    }

    public function afterSave(
        \Magento\Checkout\Model\Cart $subject,
        \Magento\Checkout\Model\Cart $result
    ): \Magento\Checkout\Model\Cart {
        if ($this->attemptId !== '') {
            $this->attemptManager->record($this->attemptId, $subject->getQuote(), $this->before);
            $this->attemptId = '';
            $this->before = [];
        }

        return $result;
    }
}
