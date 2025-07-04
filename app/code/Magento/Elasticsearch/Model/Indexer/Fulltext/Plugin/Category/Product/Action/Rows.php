<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Elasticsearch\Model\Indexer\Fulltext\Plugin\Category\Product\Action;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\CatalogSearch\Model\Indexer\Fulltext as FulltextIndexer;
use Magento\Catalog\Model\Indexer\Category\Product\Action\Rows as ActionRows;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Catalog search indexer plugin for catalog category products assignment.
 * @deprecated Elasticsearch is no longer supported by Adobe
 * @see this class will be responsible for ES only
 */
class Rows
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param TableMaintainer $tableMaintainer
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        TableMaintainer $tableMaintainer
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->storeManager = $storeManager;
        $this->connection = $resource->getConnection();
        $this->tableMaintainer = $tableMaintainer;
    }

    /**
     * Reindex after catalog category product reindex.
     *
     * @param ActionRows $subject
     * @param ActionRows $result
     * @param array $entityIds
     * @param bool $useTempTable
     * @return ActionRows
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        ActionRows $subject,
        ActionRows $result,
        array $entityIds,
        bool $useTempTable = false
    ): ActionRows {
        if (!empty($entityIds)) {
            $productIds = [];

            foreach ($this->storeManager->getStores() as $store) {
                $indexTable = $this->getIndexTable((int) $store->getId(), $useTempTable);
                $productIds[] = $this->getProductIdsFromIndex($indexTable, $entityIds);
            }

            $productIds = array_unique(array_merge([], ...$productIds));
            if (!empty($productIds)) {
                $indexer = $this->indexerRegistry->get(FulltextIndexer::INDEXER_ID);
                if ($indexer->isScheduled()) {
                    $changelog = $indexer->getView()->getChangelog();
                    $changelog->create();
                    $changelog->addList($productIds);
                } else {
                    $indexer->invalidate();
                }
            }
        }

        return $result;
    }

    /**
     * Return index table name.
     *
     * @param int $storeId
     * @param bool $useTempTable
     *
     * @return string
     */
    private function getIndexTable(int $storeId, bool $useTempTable)
    {
        return $useTempTable
            ? $this->tableMaintainer->getMainReplicaTable($storeId)
            : $this->tableMaintainer->getMainTable($storeId);
    }

    /**
     * Get all category products from index table.
     *
     * @param string $indexTable
     * @param array $categoryIds
     *
     * @return array
     */
    private function getProductIdsFromIndex(string $indexTable, array $categoryIds): array
    {
        return $this->connection->fetchCol(
            $this->connection->select()
                ->from($indexTable, ['product_id'])
                ->where('category_id IN (?)', $categoryIds)
                ->distinct()
        );
    }
}
