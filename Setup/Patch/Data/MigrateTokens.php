<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Setup\Patch\Data;

use Afterpay\Afterpay\Api\Data\CheckoutInterface;
use Afterpay\Afterpay\Api\Data\TokenInterface;
use Afterpay\Afterpay\Gateway\Config\Config;
use Afterpay\Afterpay\Model\Payment\AdditionalInformationInterface;
use Afterpay\Afterpay\Model\ResourceModel\Token;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class MigrateTokens implements DataPatchInterface
{
    protected string $paymentCode = Config::CODE;
    private Token $tokensResource;
    private SerializerInterface $serializer;
    private DateTime $dateTime;

    public function __construct(
        Token               $tokensResource,
        SerializerInterface $serializer,
        DateTime            $dateTime
    ) {
        $this->tokensResource = $tokensResource;
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [AdaptPayments::class];
    }

    public function apply(): self
    {
        $paymentsSelect = $this->tokensResource->getConnection()
            ->select()
            ->from($this->tokensResource->getTable('sales_order_payment'))
            ->where(OrderPaymentInterface::METHOD . ' = ?', $this->paymentCode);

        $payments = $this->tokensResource->getConnection()->fetchAll($paymentsSelect);
        $tokenEntries = [];
        foreach ($payments as $payment) {
            if (!empty($payment[OrderPaymentInterface::ADDITIONAL_INFORMATION])) {
                $additionalInfo = $this->serializer->unserialize($payment[OrderPaymentInterface::ADDITIONAL_INFORMATION]);
                $token = $additionalInfo[CheckoutInterface::AFTERPAY_TOKEN];
                $expiration = $additionalInfo[AdditionalInformationInterface::AFTERPAY_AUTH_EXPIRY_DATE] ?? null;
                if ($expiration) {
                    $expiration = $this->dateTime->formatDate($expiration);
                }
                $tokenEntries[] = [
                    TokenInterface::ORDER_ID_FIELD        => $payment['parent_id'],
                    TokenInterface::TOKEN_FIELD           => $token,
                    TokenInterface::EXPIRATION_DATE_FIELD => $expiration
                ];
            }
        }

        if (!empty($tokenEntries)) {
            $this->tokensResource->getConnection()->insertOnDuplicate($this->tokensResource->getMainTable(), $tokenEntries);
        }

        return $this;
    }
}
