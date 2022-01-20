<?php

namespace MageSuite\StuckIndexersFixer\Cron;

class FixStuckIndexers
{
    protected \MageSuite\StuckIndexersFixer\Model\FixStuckIndexers $fixStuckIndexers;

    public function __construct(\MageSuite\StuckIndexersFixer\Model\FixStuckIndexers $fixStuckIndexers)
    {
        $this->fixStuckIndexers = $fixStuckIndexers;
    }

    public function execute()
    {
        $this->fixStuckIndexers->execute();
    }
}
