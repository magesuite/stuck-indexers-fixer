<?php

namespace MageSuite\StuckIndexersFixer\Test\Integration\Model;

class FixStuckIndexersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Indexer\Model\Indexer\StateFactory
     */
    protected $indexerStateFactory;

    /**
     * @var \MageSuite\StuckIndexersFixer\Model\FixStuckIndexers|mixed
     */
    protected $stuckIndexerFixer;

    /**
     * @var \Magento\Framework\App\ResourceConnection|mixed
     */
    protected $connection;
    /**
     * @var \Magento\Framework\Mview\View\StateInterfaceFactory
     */
    protected $mviewStateFactory;

    protected function setUp(): void
    {
        $objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->indexerStateFactory = $objectManager->get(\Magento\Indexer\Model\Indexer\StateFactory::class);
        $this->mviewStateFactory = $objectManager->get(\Magento\Framework\Mview\View\StateInterfaceFactory::class);
        $this->stuckIndexerFixer = $objectManager->create(\MageSuite\StuckIndexersFixer\Model\FixStuckIndexers::class);
        $this->connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);

        parent::setUp();
    }

    /**
     * @magentoDbIsolation enabled
     * @dataProvider indexerTestCases
     */
    public function testItFixesOnlyStuckIndexers(string $currentStatus, string $lastUpdated, string $expectedStatus): void
    {
        $connection = $this->connection->getConnection();
        $connection->update(
            'indexer_state',
            [
                'status' => $currentStatus,
                'updated' => $lastUpdated
            ],
            ['indexer_id = ?' => \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID]
        );

        $this->stuckIndexerFixer->execute();

        $indexer = $this->indexerStateFactory->create()->load(
            \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID,
            'indexer_id'
        );

        $this->assertEquals($expectedStatus, $indexer->getStatus());
    }

    /**
     * @magentoDbIsolation enabled
     * @dataProvider mviewTestCases
     */
    public function testItFixesStuckAndSuspendedViews(string $currentStatus, string $lastUpdated, string $expectedStatus): void
    {
        $this->updateStateStatus($currentStatus, $lastUpdated);
        $this->stuckIndexerFixer->execute();
        $indexer = $this->mviewStateFactory->create()->load(
            \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID,
            'view_id'
        );

        $this->assertEquals($expectedStatus, $indexer->getStatus());
    }

    public static function indexerTestCases()
    {
        return [
            [
                'working',
                self::prepareTime('-121 minute'),
                'invalid'
            ],
            [
                'working',
                self::prepareTime('-1 minute'),
                'working'
            ],
            [
                'working',
                self::prepareTime('-2 minute'),
                'working'
            ],
        ];
    }

    public static function mviewTestCases()
    {
        return [
            [
                'working',
                self::prepareTime('-121 minute'),
                'idle'
            ],
            [
                'working',
                self::prepareTime('-1 minute'),
                'working'
            ],
            [
                'working',
                self::prepareTime('-2 minute'),
                'working'
            ],
            [
                'suspended',
                self::prepareTime('-1 minute'),
                'idle'
            ],
        ];
    }

    protected static function prepareTime($modify = null)
    {
        $dateTime = new \DateTime();

        if ($modify !== null) {
            $dateTime->modify($modify);
        }

        return $dateTime->format(\DateTime::ATOM);
    }

    protected function updateStateStatus(string $currentStatus, string $lastUpdated): void
    {
        $connection = $this->connection->getConnection();
        $tableName = $connection->getTableName('mview_state');
        $select = $connection->select()->from($tableName, 'state_id')
            ->where('view_id = ?', \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID);
        $stateId = (int)$connection->fetchOne($select);

        if ($stateId) {
            $connection->update(
                $tableName,
                [
                    'status' => $currentStatus,
                    'updated' => $lastUpdated,
                ],
                ['state_id = ?' => $stateId]
            );
            return;
        }

        $connection->insert(
            $tableName,
            [
                'view_id' => \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID,
                'status' => $currentStatus,
                'updated' => $lastUpdated,
                'version_id' => 0,
                'mode' => 'enabled'
            ]
        );
    }
}
