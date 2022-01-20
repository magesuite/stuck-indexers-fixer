<?php

namespace MageSuite\StuckIndexersFixer\Helper;

class Configuration
{
    const XML_PATH_STUCK_INDEXER_FIXER_CONFIG = 'system/stuck_indexers_fixer';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $config = null;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->scopeConfig = $scopeConfigInterface;
    }

    public function isAutomaticFixingEnabled(): bool
    {
        return $this->getConfig()->getIsAutomaticFixingEnabled();
    }

    public function getThresholdToMarkIndexerAsStuck(): int
    {
        return $this->getConfig()->getThresholdToMarkIndexerAsStuck();
    }

    protected function getConfig()
    {
        if ($this->config === null) {
            $config = $this->scopeConfig->getValue(self::XML_PATH_STUCK_INDEXER_FIXER_CONFIG);

            if (!is_array($config) || $config === null) {
                $config = [];
            }

            $this->config = new \Magento\Framework\DataObject($config);
        }

        return $this->config;
    }
}
