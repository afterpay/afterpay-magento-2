<?php

declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Log\Method;

class Logger extends \Magento\Payment\Model\Method\Logger
{
    private $forceDebug = false;

    public function setForceDebug(bool $forceDebug): self
    {
        $this->forceDebug = $forceDebug;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function debug(array $data, array $maskKeys = null, $forceDebug = null)
    {
        if ($forceDebug === null) {
            $forceDebug = $this->forceDebug ? true : null;
        }
        parent::debug($data, $maskKeys, $forceDebug);
    }

    protected function filterDebugData(
        array $debugData,
        array $debugReplacePrivateDataKeys,
        bool $maskAll = false
    ): array {
        $debugReplacePrivateDataKeys = array_map('strtolower', $debugReplacePrivateDataKeys);

        foreach (array_keys($debugData) as $key) {
            $isKeyToReplace = in_array(strtolower((string)$key), $debugReplacePrivateDataKeys);
            $isMasked = !is_array($debugData[$key]) && ($isKeyToReplace || $maskAll);
            if ($isMasked) {
                $debugData[$key] = self::DEBUG_KEYS_MASK;
            } elseif ($isKeyToReplace && is_array($debugData[$key])) {
                $debugData[$key] = $this->filterDebugData(
                    $debugData[$key],
                    $debugReplacePrivateDataKeys,
                    true
                );
            } elseif (is_array($debugData[$key])) {
                $debugData[$key] = $this->filterDebugData($debugData[$key], $debugReplacePrivateDataKeys);
            }
        }
        return $debugData;
    }
}
