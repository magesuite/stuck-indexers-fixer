<?php

namespace MageSuite\StuckIndexersFixer\Model;

class FixStuckIndexers
{
    protected \MageSuite\StuckIndexersFixer\Model\ResourceModel\IndexerState $indexerState;
    protected \MageSuite\StuckIndexersFixer\Model\ResourceModel\MviewState $mviewState;
    protected \MageSuite\StuckIndexersFixer\Helper\Configuration $configuration;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \MageSuite\StuckIndexersFixer\Model\ResourceModel\IndexerState $indexerState,
        \MageSuite\StuckIndexersFixer\Model\ResourceModel\MviewState $mviewState,
        \MageSuite\StuckIndexersFixer\Helper\Configuration $configuration,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->indexerState = $indexerState;
        $this->mviewState = $mviewState;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->configuration->isAutomaticFixingEnabled()) {
            return;
        }

        $thresholdToMarkIndexerAsStuck = $this->configuration->getThresholdToMarkIndexerAsStuck();

        $this->fixStuckIndexers($thresholdToMarkIndexerAsStuck);
    }

    protected function fixStuckIndexers($threshold)
    {
        $stuckMview = $this->mviewState->getStuckIndexers($threshold) ?? [];

        foreach ($stuckMview as $mview) {
            $this->mviewState->setIndexerAsIdle($mview['view_id']);

            $this->logger->info(sprintf('Detected and unblocked stuck view %s', $mview['view_id']));
        }

        $stuckFull = $this->indexerState->getStuckIndexers($threshold) ?? [];

        foreach ($stuckFull as $indexer) {
            $this->indexerState->setIndexerAsInvalid($indexer['indexer_id']);

            $this->logger->info(sprintf('Detected and unblocked stuck indexer %s', $indexer['indexer_id']));
        }
    }
}
