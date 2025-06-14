<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\CatalogInventory\Model\Indexer\Stock\Action;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher;
use Magento\CatalogInventory\Model\Indexer\Stock\AbstractAction;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\ResourceModel\Indexer\Stock\DefaultStock;
use Magento\CatalogInventory\Model\ResourceModel\Indexer\Stock\StockInterface;
use Magento\CatalogInventory\Model\ResourceModel\Indexer\StockFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorInterface;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\BatchProviderInterface;
use Magento\Framework\Indexer\BatchSizeManagementInterface;
use Magento\Framework\Indexer\CacheContext;

/**
 * Class Full reindex action
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Full extends AbstractAction
{
    /**
     * Action type representation
     */
    public const ACTION_TYPE = 'full';

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var BatchSizeManagementInterface
     */
    private $batchSizeManagement;

    /**
     * @var BatchProviderInterface
     */
    private $batchProvider;

    /**
     * @var array
     */
    private $batchRowsCount;

    /**
     * @var ActiveTableSwitcher
     */
    private $activeTableSwitcher;

    /**
     * @var QueryGenerator|null
     */
    private $batchQueryGenerator;

    /**
     * @var DeploymentConfig|null
     */
    private $deploymentConfig;

    /**
     * Deployment config path
     *
     * @var string
     */
    private const DEPLOYMENT_CONFIG_INDEXER_BATCHES = 'indexer/batch_size/';

    /**
     * @param ResourceConnection $resource
     * @param StockFactory $indexerFactory
     * @param ProductType $catalogProductType
     * @param CacheContext $cacheContext
     * @param EventManager $eventManager
     * @param MetadataPool|null $metadataPool
     * @param BatchSizeManagementInterface|null $batchSizeManagement
     * @param BatchProviderInterface|null $batchProvider
     * @param array $batchRowsCount
     * @param ActiveTableSwitcher|null $activeTableSwitcher
     * @param QueryGenerator|null $batchQueryGenerator
     * @param DeploymentConfig|null $deploymentConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resource,
        StockFactory $indexerFactory,
        ProductType $catalogProductType,
        CacheContext $cacheContext,
        EventManager $eventManager,
        ?MetadataPool $metadataPool = null,
        ?BatchSizeManagementInterface $batchSizeManagement = null,
        ?BatchProviderInterface $batchProvider = null,
        array $batchRowsCount = [],
        ?ActiveTableSwitcher $activeTableSwitcher = null,
        ?QueryGenerator $batchQueryGenerator = null,
        ?DeploymentConfig $deploymentConfig = null
    ) {
        parent::__construct(
            $resource,
            $indexerFactory,
            $catalogProductType,
            $cacheContext,
            $eventManager
        );

        $this->metadataPool = $metadataPool ?: ObjectManager::getInstance()->get(MetadataPool::class);
        $this->batchProvider = $batchProvider ?: ObjectManager::getInstance()->get(BatchProviderInterface::class);
        $this->batchSizeManagement = $batchSizeManagement ?: ObjectManager::getInstance()->get(
            BatchSizeManagementInterface::class
        );
        $this->batchRowsCount = $batchRowsCount;
        $this->activeTableSwitcher = $activeTableSwitcher ?: ObjectManager::getInstance()
            ->get(ActiveTableSwitcher::class);
        $this->batchQueryGenerator = $batchQueryGenerator ?: ObjectManager::getInstance()->get(QueryGenerator::class);
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Execute Full reindex
     *
     * @param null|array $ids
     * @throws LocalizedException
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($ids = null): void
    {
        try {
            $this->useIdxTable(false);
            $this->cleanIndexersTables($this->_getTypeIndexers());

            $entityMetadata = $this->metadataPool->getMetadata(ProductInterface::class);

            $columns = array_keys($this->_getConnection()->describeTable($this->_getIdxTable()));
            $indexerTables = [];

            /** @var DefaultStock $indexer */
            foreach ($this->_getTypeIndexers() as $indexer) {
                $indexer->setActionType(self::ACTION_TYPE);
                $connection = $indexer->getConnection();
                $tableName = $this->activeTableSwitcher->getAdditionalTableName($indexer->getMainTable());

                $batchRowCount = $this->deploymentConfig->get(
                    self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . Processor::INDEXER_ID . '/' . $indexer->getTypeId(),
                    $this->deploymentConfig->get(
                        self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . Processor::INDEXER_ID . '/' . 'default'
                    )
                );

                if ($batchRowCount === null) {
                    $batchRowCount = isset($this->batchRowsCount[$indexer->getTypeId()])
                        ? $this->batchRowsCount[$indexer->getTypeId()]
                        : $this->batchRowsCount['default'];
                }

                $this->batchSizeManagement->ensureBatchSize($connection, $batchRowCount);

                $select = $connection->select();
                $select->distinct(true);
                $select->from(
                    [
                        'e' => $entityMetadata->getEntityTable()
                    ],
                    $entityMetadata->getIdentifierField()
                )->where(
                    'type_id = ?',
                    $indexer->getTypeId()
                );

                $batchQueries = $this->batchQueryGenerator->generate(
                    $entityMetadata->getIdentifierField(),
                    $select,
                    $batchRowCount,
                    BatchIteratorInterface::UNIQUE_FIELD_ITERATOR
                );

                foreach ($batchQueries as $query) {
                    $this->clearTemporaryIndexTable();
                    $entityIds = $connection->fetchCol($query);
                    if (!empty($entityIds)) {
                        $indexer->reindexEntity($entityIds);
                        $select = $connection->select()->from($this->_getIdxTable(), $columns);
                        $query = $select->insertFromSelect($tableName, $columns);
                        $connection->query($query);
                    }
                }

                $indexerTables[] = $indexer->getMainTable();
            }

            $indexerTables = array_unique($indexerTables);
            $this->activeTableSwitcher->switchTable($this->_getConnection(), $indexerTables);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * Delete all records from index table
     *
     * Used to clean table before re-indexation
     *
     * @param array $indexers
     * @return void
     */
    private function cleanIndexersTables(array $indexers): void
    {
        $tables = array_map(
            function (StockInterface $indexer) {
                return $this->activeTableSwitcher->getAdditionalTableName($indexer->getMainTable());
            },
            $indexers
        );

        $tables = array_unique($tables);

        foreach ($tables as $table) {
            $this->_getConnection()->truncateTable($table);
        }
    }
}
