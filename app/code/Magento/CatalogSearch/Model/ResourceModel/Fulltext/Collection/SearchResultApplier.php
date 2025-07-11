<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

namespace Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection;

/**
 * Resolve specific attributes for search criteria.
 *
 * @deprecated mysql search engine has been removed
 * @see \Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplier
 */
class SearchResultApplier implements SearchResultApplierInterface
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var SearchResultInterface
     */
    private $searchResult;

    /**
     * @param Collection $collection
     * @param SearchResultInterface $searchResult
     */
    public function __construct(
        Collection $collection,
        SearchResultInterface $searchResult
    ) {
        $this->collection = $collection;
        $this->searchResult = $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        if (empty($this->searchResult->getItems())) {
            $this->collection->getSelect()->where('NULL');
            return;
        }

        $ids = [];
        foreach ($this->searchResult->getItems() as $item) {
            $ids[] = (int)$item->getId();
        }

        $orderList = implode(',', $ids);
        $this->collection->getSelect()
            ->where('e.entity_id IN (?)', $ids)
            ->reset(\Magento\Framework\DB\Select::ORDER)
            ->order(new \Magento\Framework\DB\Sql\Expression("FIELD(e.entity_id, $orderList)"));
    }
}
