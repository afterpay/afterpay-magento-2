<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Controller\Express;

class RevertPdp implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonFactory;
    private \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Afterpay\Afterpay\Model\ExpressCheckout\PdpAttemptManager $attemptManager
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->attemptManager = $attemptManager;
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $reverted = $this->attemptManager->revert(
            (string)$this->request->getParam('express_attempt')
        );

        return $this->jsonFactory->create()->setData(['success' => $reverted]);
    }
}
