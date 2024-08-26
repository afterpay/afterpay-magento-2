<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Response\MerchantConfiguration;

use Afterpay\Afterpay\Model\Config;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CreditMemoOnGrandTotalConfigurationHandler implements HandlerInterface
{
    private Config $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $websiteId = (int)$handlingSubject['websiteId'];
        $mpid = $response['publicId'] ?? '';
        $flagValue = false; // TODO: replace it with a flag pull
        $this->config->setIsCreditMemoGrandTotalOnlyEnabled((int)$flagValue, $websiteId);
    }
}
