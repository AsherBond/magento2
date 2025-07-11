<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogImportExport\Model\Export\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogImportExport\Model\Export\ProductFilterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Stock status filter for products export
 */
class StockStatusFilter implements ProductFilterInterface
{
    private const NAME = 'quantity_and_stock_status';
    private const IN_STOCK = '1';
    private const OUT_OF_STOCK = '0';
    /**
     * @var Stock
     */
    private $stockHelper;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Stock $stockHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Stock $stockHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->stockHelper = $stockHelper;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * @inheritDoc
     */
    public function filter(Collection $collection, array $filters): Collection
    {
        $value = $filters[self::NAME] ?? '';
        switch ($value) {
            case self::IN_STOCK:
                $this->stockHelper->addInStockFilterToCollection($collection);
                $collection->setFlag(self::NAME . '_filter_applied');
                break;
            case self::OUT_OF_STOCK:
                $this->stockHelper->addOutOfStockFilterToCollection($collection);
                $collection->setFlag(self::NAME . '_filter_applied');
                break;
        }
        return $collection;
    }
}
