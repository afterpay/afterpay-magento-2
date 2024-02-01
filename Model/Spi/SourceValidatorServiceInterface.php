<?php
declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Spi;

/**
 * Process source validation
 *
 * @api
 */
interface SourceValidatorServiceInterface
{
    /**
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     * @return void
     */
    public function execute(\Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface $sourceDeductionRequest): void;   // @codingStandardsIgnoreLine
}
