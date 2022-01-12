<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Validator\Method;

class NotAllowedProductsValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    private \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Afterpay\Afterpay\Model\ResourceModel\NotAllowedProductsProvider $notAllowedProductsProvider
    ) {
        parent::__construct($resultFactory);
        $this->notAllowedProductsProvider = $notAllowedProductsProvider;
    }

    public function validate(array $validationSubject): \Magento\Payment\Gateway\Validator\ResultInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $validationSubject['quote'];

        $disallowedProductsIds = $this->notAllowedProductsProvider->provideIds((int)$quote->getStoreId());
        $disallowedProductsIdsAsKeys = array_flip($disallowedProductsIds);

        foreach ($quote->getItems() ?? [] as $item) {
            if (isset($disallowedProductsIdsAsKeys[(int)$item->getProductId()])) {
                return $this->createResult(false);
            }
        }
        return $this->createResult(true);
    }
}
