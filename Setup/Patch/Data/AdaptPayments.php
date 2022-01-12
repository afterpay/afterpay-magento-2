<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Setup\Patch\Data;

class AdaptPayments implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private const METHOD_CODE = 'afterpaypayovertime';

    private $salesSetup;

    public function __construct(
        \Magento\Sales\Setup\SalesSetup $salesSetup
    ) {
        $this->salesSetup = $salesSetup;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function apply(): self
    {
        $this->salesSetup->getConnection()
            ->update(
                $this->salesSetup->getTable('sales_order_payment'),
                [
                    'method' => \Afterpay\Afterpay\Gateway\Config\Config::CODE,
                    'additional_information' => new \Zend_Db_Expr(
                        'replace(additional_information, "afterpay_payment_status", "afterpay_payment_state")'
                    )
                ],
                ['method = ?' => self::METHOD_CODE]
            );

        return $this;
    }
}
