<?php

namespace MageSuite\StuckIndexersFixer\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = \Monolog\Logger::INFO;
    protected $fileName = '/var/log/stuck_indexers_fixer.log';
}
