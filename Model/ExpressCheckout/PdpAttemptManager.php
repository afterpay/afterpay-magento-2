<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\ExpressCheckout;

class PdpAttemptManager
{
    private const SESSION_KEY = 'afterpay_pdp_express_attempts';

    private \Magento\Checkout\Model\Session $checkoutSession;
    private \Magento\Quote\Api\CartRepositoryInterfaceFactory $cartRepositoryFactory;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterfaceFactory $cartRepositoryFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartRepositoryFactory = $cartRepositoryFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function snapshot(\Magento\Quote\Model\Quote $quote): array
    {
        $quantities = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $quantities[(int)$item->getId()] = (float)$item->getQty();
        }

        return $quantities;
    }

    public function record(string $attemptId, \Magento\Quote\Model\Quote $quote, array $before): void
    {
        if (!$this->isValidAttemptId($attemptId)) {
            return;
        }

        $quoteId = (int)$quote->getId();
        if ($quoteId > 0) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepositoryFactory->create()->getActive($quoteId);
        }

        $deltas = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $itemId = (int)$item->getId();
            $delta = (float)$item->getQty() - ($before[$itemId] ?? 0.0);
            if ($delta > 0) {
                $deltas[$itemId] = $delta;
            }
        }

        $attempts = (array)$this->checkoutSession->getData(self::SESSION_KEY);
        $attempts[$attemptId] = [
            'quote_id' => $quoteId,
            'deltas' => $deltas,
        ];
        $this->checkoutSession->setData(self::SESSION_KEY, $attempts);
    }

    public function has(string $attemptId): bool
    {
        if (!$this->isValidAttemptId($attemptId)) {
            return false;
        }

        $attempts = (array)$this->checkoutSession->getData(self::SESSION_KEY);
        return !empty($attempts[$attemptId]['deltas']);
    }

    public function revert(string $attemptId): bool
    {
        if (!$this->isValidAttemptId($attemptId)) {
            return false;
        }

        $attempts = (array)$this->checkoutSession->getData(self::SESSION_KEY);
        $attempt = $attempts[$attemptId] ?? null;
        if (!$attempt) {
            return false;
        }

        $sessionQuote = $this->checkoutSession->getQuote();
        $quoteId = (int)$sessionQuote->getId();
        if ($quoteId !== (int)$attempt['quote_id']) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName('quote'), ['entity_id'])
                ->where('entity_id = ?', $quoteId)
                ->forUpdate(true);
            if (!$connection->fetchOne($select)) {
                $connection->rollBack();
                return false;
            }

            /** @var \Magento\Quote\Model\Quote $quote */
            $cartRepository = $this->cartRepositoryFactory->create();
            $quote = $cartRepository->getActive($quoteId);
            $expectedQuantities = [];
            foreach ($attempt['deltas'] as $itemId => $delta) {
                $item = $quote->getItemById((int)$itemId);
                if (!$item) {
                    continue;
                }

                $quantity = (float)$item->getQty() - (float)$delta;
                $expectedQuantities[(int)$itemId] = max(0.0, $quantity);
                if ($quantity > 0) {
                    $item->setQty($quantity);
                } else {
                    $quote->removeItem((int)$itemId);
                }
            }

            $quote->collectTotals();
            $cartRepository->save($quote);
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepositoryFactory->create()->getActive($quoteId);
        $isReverted = true;
        foreach ($expectedQuantities as $itemId => $expectedQuantity) {
            $item = $quote->getItemById($itemId);
            if (($expectedQuantity === 0.0 && $item)
                || ($expectedQuantity > 0.0
                    && (!$item || abs((float)$item->getQty() - $expectedQuantity) > 0.0001))
            ) {
                $isReverted = false;
                break;
            }
        }

        unset($attempts[$attemptId]);
        $this->checkoutSession->setData(self::SESSION_KEY, $attempts);
        $this->checkoutSession->replaceQuote($quote);

        return $isReverted;
    }

    public function discard(string $attemptId): void
    {
        $attempts = (array)$this->checkoutSession->getData(self::SESSION_KEY);
        unset($attempts[$attemptId]);
        $this->checkoutSession->setData(self::SESSION_KEY, $attempts);
    }

    private function isValidAttemptId(string $attemptId): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_-]{1,64}$/', $attemptId);
    }
}
