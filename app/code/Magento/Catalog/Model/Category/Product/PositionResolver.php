<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Category\Product;

/**
 * Resolver to get product positions by ids assigned to specific category
 */
class PositionResolver extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('catalog_product_entity', 'entity_id');
    }

    /**
     * Get category product positions
     *
     * @param int $categoryId
     * @return array
     */
    public function getPositions(int $categoryId): array
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            ['cpe' => $this->getTable('catalog_product_entity')],
            'entity_id'
        )->joinLeft(
            ['ccp' => $this->getTable('catalog_category_product')],
            'ccp.product_id=cpe.entity_id'
        )->where(
            'ccp.category_id = ?',
            $categoryId
        )->order(
            'ccp.position ' . \Magento\Framework\DB\Select::SQL_ASC
        )->order(
            'ccp.product_id ' . \Magento\Framework\DB\Select::SQL_DESC
        );

        return array_flip($connection->fetchCol($select));
    }

    /**
     * Get category product minimum position
     *
     * @param int $categoryId
     * @return int
     */
    public function getMinPosition(int $categoryId): int
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            ['cpe' => $this->getTable('catalog_product_entity')],
            ['position' => new \Zend_Db_Expr('MIN(position)')]
        )->joinLeft(
            ['ccp' => $this->getTable('catalog_category_product')],
            'ccp.product_id=cpe.entity_id'
        )->where(
            'ccp.category_id = ?',
            $categoryId
        )->order(
            'ccp.product_id ' . \Magento\Framework\DB\Select::SQL_DESC
        );

        return (int)$connection->fetchOne($select);
    }
}
