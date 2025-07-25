<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogRule\Model\Indexer;

use Magento\Framework\App\ResourceConnection;

/**
 * @inheritDoc
 */
class IndexerTableSwapper implements IndexerTableSwapperInterface
{
    /**
     * Keys are original tables' names, values - created temporary tables.
     *
     * @var string[]
     */
    private $temporaryTables = [];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resourceConnection = $resource;
    }

    /**
     * Create temporary table based on given table to use instead of original.
     *
     * @param string $originalTableName
     *
     * @return string Created table name.
     */
    private function createTemporaryTable(string $originalTableName): string
    {
        $temporaryTableName = $this->resourceConnection->getTableName(
            $originalTableName . '__temp' . $this->generateRandomSuffix()
        );

        $this->resourceConnection->getConnection()->query(
            sprintf(
                'create table %s like %s',
                $temporaryTableName,
                $this->resourceConnection->getTableName($originalTableName)
            )
        );

        return $temporaryTableName;
    }

    /**
     * Random suffix for temporary tables not to conflict with each other.
     *
     * @return string
     */
    private function generateRandomSuffix(): string
    {
        return bin2hex(random_bytes(4));
    }

    /**
     * @inheritDoc
     */
    public function getWorkingTableName(string $originalTable): string
    {
        $originalTable = $this->resourceConnection->getTableName($originalTable);
        if (!array_key_exists($originalTable, $this->temporaryTables)) {
            $this->temporaryTables[$originalTable] = $this->createTemporaryTable($originalTable);
        }

        return $this->temporaryTables[$originalTable];
    }

    /**
     * @inheritDoc
     */
    public function swapIndexTables(array $originalTablesNames)
    {
        $toRename = [];
        /** @var string[] $toDrop */
        $toDrop = [];
        /** @var string[] $temporaryTablesRenamed */
        $temporaryTablesRenamed = [];
        $restoreTriggerQueries = [];
        //Renaming temporary tables to original tables' names, dropping old
        //tables.
        foreach ($originalTablesNames as $tableName) {
            $tableName = $this->resourceConnection->getTableName($tableName);
            $temporaryOriginalName = $this->resourceConnection->getTableName(
                $tableName . $this->generateRandomSuffix()
            );
            $temporaryTableName = $this->getWorkingTableName($tableName);
            $restoreTriggerQueries[] = $this->getRestoreTriggerQueries($tableName);
            $toRename[] = [
                'oldName' => $tableName,
                'newName' => $temporaryOriginalName,
            ];
            $toRename[] = [
                'oldName' => $temporaryTableName,
                'newName' => $tableName,
            ];
            $toDrop[] = $temporaryOriginalName;
            $temporaryTablesRenamed[] = $tableName;
        }

        //Swapping tables.
        $this->resourceConnection->getConnection()->renameTablesBatch($toRename);
        //Cleaning up.
        foreach ($temporaryTablesRenamed as $tableName) {
            unset($this->temporaryTables[$tableName]);
        }
        //Removing old ones.
        foreach ($toDrop as $tableName) {
            $this->resourceConnection->getConnection()->dropTable($tableName);
        }

        //Restoring triggers
        $restoreTriggerQueries = array_merge([], ...$restoreTriggerQueries);
        foreach ($restoreTriggerQueries as $restoreTriggerQuery) {
            $this->resourceConnection->getConnection()->multiQuery($restoreTriggerQuery);
        }
    }

    /**
     * Get queries for table triggers restoring.
     *
     * @param string $tableName
     * @return array
     */
    private function getRestoreTriggerQueries(string $tableName): array
    {
        $triggers = $this->resourceConnection->getConnection()
            ->query('SHOW TRIGGERS LIKE \''. $tableName . '\'')
            ->fetchAll();

        if (!$triggers) {
            return [];
        }

        $result = [];
        foreach ($triggers as $trigger) {
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            $result[] = 'DROP TRIGGER IF EXISTS ' . $trigger['Trigger'];
            $triggerData = $this->resourceConnection->getConnection()
                ->query('SHOW CREATE TRIGGER '. $trigger['Trigger'])
                ->fetch();
            $result[]  = preg_replace('/DEFINER=[^\s]*/', '', $triggerData['SQL Original Statement']);
        }

        return $result;
    }

    /**
     * Cleanup leftover temporary tables
     */
    public function __destruct()
    {
        foreach ($this->temporaryTables as $tableName) {
            $this->resourceConnection->getConnection()->dropTable($tableName);
        }
    }
}
