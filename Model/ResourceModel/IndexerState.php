<?php

namespace MageSuite\StuckIndexersFixer\Model\ResourceModel;

class IndexerState
{
    protected \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getStuckIndexers($stuckDetectionThreshold)
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select();
        $select->from($connection->getTableName('indexer_state'));
        $select->where('status = ?', 'working');
        $select->where(sprintf('updated < date_sub(NOW(), INTERVAL %s Minute)', $stuckDetectionThreshold));

        return $connection->fetchAll($select);
    }

    public function setIndexerAsInvalid($indexerId)
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->update(
            $connection->getTableName('indexer_state'),
            ['status' => 'invalid'],
            ['indexer_id = ?' => $indexerId]
        );
    }
}
