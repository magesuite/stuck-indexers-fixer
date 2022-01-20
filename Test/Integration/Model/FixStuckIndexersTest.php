<?php

namespace MageSuite\StuckIndexersFixer\Test\Integration\Model;

class FixStuckIndexersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

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

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->indexerStateFactory = $this->objectManager->get(\Magento\Indexer\Model\Indexer\StateFactory::class);
        $this->mviewStateFactory = $this->objectManager->get(\Magento\Framework\Mview\View\StateInterfaceFactory::class);
        $this->stuckIndexerFixer = $this->objectManager->create(\MageSuite\StuckIndexersFixer\Model\FixStuckIndexers::class);
        $this->connection = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);

        parent::setUp();
    }

    /**
     * @magentoDbIsolation enabled
     * @dataProvider indexerTestCases
     */
    public function testItFixesOnlyStuckIndexers($currentStatus, $lastUpdated, $expectedStatus)
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
    public function testItFixesOnlyStuckViews($currentStatus, $lastUpdated, $expectedStatus)
    {
        $connection = $this->connection->getConnection();
        $connection->insert(
            'mview_state',
            [
                'view_id' => \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID,
                'status' => $currentStatus,
                'updated' => $lastUpdated,
                'version_id' => 0,
                'mode' => 'enabled'
            ],
        );

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
}
