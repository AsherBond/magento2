<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\CatalogInventory\Api;

/**
 * Interface StockItemRepository
 * @api
 * @since 100.0.2
 *
 * @deprecated 100.3.0 Replaced with Multi Source Inventory
 * @link https://developer.adobe.com/commerce/webapi/rest/inventory/index.html
 * @link https://developer.adobe.com/commerce/webapi/rest/inventory/inventory-api-reference.html
 */
interface StockItemRepositoryInterface
{
    /**
     * Save Stock Item data
     *
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function save(\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem);

    /**
     * Load Stock Item data by given stockId and parameters
     *
     * @param int $stockItemId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function get($stockItemId);

    /**
     * Load Stock Item data collection by given search criteria
     *
     * @param \Magento\CatalogInventory\Api\StockItemCriteriaInterface $criteria
     * @return \Magento\CatalogInventory\Api\Data\StockItemCollectionInterface
     */
    public function getList(\Magento\CatalogInventory\Api\StockItemCriteriaInterface $criteria);

    /**
     * Delete stock item
     *
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @return bool
     */
    public function delete(\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem);

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById($id);
}
