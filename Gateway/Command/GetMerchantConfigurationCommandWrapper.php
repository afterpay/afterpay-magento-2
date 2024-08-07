<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Gateway\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class GetMerchantConfigurationCommandWrapper implements \Magento\Payment\Gateway\CommandInterface
{
    public const DEFAULT_WEBSITE_ID = 0;

    private \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand;
    private \Afterpay\Afterpay\Model\Config $afterpayConfig;
    private \Magento\Framework\App\Config\ReinitableConfigInterface $appConfig;
    private \Afterpay\Afterpay\Model\Log\Method\Logger $debugLogger;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Payment\Gateway\CommandInterface $merchantConfigurationCommand,
        \Afterpay\Afterpay\Model\Config $afterpayConfig,
        \Magento\Framework\App\Config\ReinitableConfigInterface $appConfig,
        \Afterpay\Afterpay\Model\Log\Method\Logger $debugLogger,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->merchantConfigurationCommand = $merchantConfigurationCommand;
        $this->afterpayConfig = $afterpayConfig;
        $this->appConfig = $appConfig;
        $this->debugLogger = $debugLogger;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        $websiteId = (int)$commandSubject['websiteId'];
        $scope = ScopeInterface::SCOPE_WEBSITE;
        if ($websiteId === self::DEFAULT_WEBSITE_ID) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
        $websiteHasOwnConfig = $this->afterpayConfig->websiteHasOwnConfig($websiteId);
        try {
            if (!$websiteHasOwnConfig) {
                $this->eraseMerchantConfiguration($websiteId, $websiteHasOwnConfig);

                return null;
            }

            if ($this->afterpayConfig->getIsPaymentActive($websiteId) === true) {
                $this->checkCountry($scope, $websiteId);
                $this->checkCurrency($scope, $websiteId);
                $this->debugLogger->setForceDebug($this->afterpayConfig->getIsDebug($websiteId));

                return $this->merchantConfigurationCommand->execute($commandSubject);
            }
            // Disable Cash App Pay if Afterpay is disabled
            $this->afterpayConfig->setCashAppPayActive(0, $websiteId);

            return null;
        } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
            $this->eraseMerchantConfiguration($websiteId, $websiteHasOwnConfig);
            $this->logger->notice($e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->eraseMerchantConfiguration($websiteId, $websiteHasOwnConfig);
            throw $e;
        } finally {
            $this->appConfig->reinit();
        }
    }

    private function eraseMerchantConfiguration(int $websiteId, bool $websiteHasOwnConfig): void
    {
        $this->afterpayConfig
            ->deleteMaxOrderTotal($websiteId, $websiteHasOwnConfig)
            ->deleteMinOrderTotal($websiteId, $websiteHasOwnConfig)
            ->deleteCbtCurrencyLimits($websiteId, $websiteHasOwnConfig);
        $this->afterpayConfig->deleteSpecificCountries($websiteId, $websiteHasOwnConfig);
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function checkCountry(string $scope, int $websiteId): void
    {

        $merchantCountry = $this->afterpayConfig->getMerchantCountry(
            $scope,
            $websiteId
        );
        $allowedCountries = $this->afterpayConfig->getAllowedCountries($websiteId);
        if (!in_array($merchantCountry, $allowedCountries)) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
            // @codingStandardsIgnoreLine
                __('Unable to fetch Afterpay merchant configuration due to unsupported merchant country. Supported countries: %1.', implode(', ', $allowedCountries))
            );
        }
    }

    /**
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function checkCurrency(string $scope, int $websiteId): void
    {

        $merchantCurrency = $this->afterpayConfig->getMerchantCurrency(
            $scope,
            $websiteId
        );
        $allowedCurrencies = $this->afterpayConfig->getAllowedCurrencies($websiteId);
        if (!in_array($merchantCurrency, $allowedCurrencies)) {
            throw new \Magento\Payment\Gateway\Command\CommandException(
            // @codingStandardsIgnoreLine
                __('Unable to fetch Afterpay merchant configuration due to unsupported merchant currency. Supported currencies: %1.', implode(', ', $allowedCurrencies))
            );
        }
    }
}
