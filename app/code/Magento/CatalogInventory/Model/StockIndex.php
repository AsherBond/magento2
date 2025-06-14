<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */

namespace Magento\CatalogInventory\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Website as ProductWebsite;
use Magento\CatalogInventory\Api\StockIndexInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResourceModel;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Website;

/**
 * Index responsible for Stock
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockIndex implements StockIndexInterface
{
    /**
     * @var StockRegistryProviderInterface
     */
    protected $stockRegistryProvider;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StockStatusResourceModel
     */
    protected $stockStatusResource;

    /**
     * @var ProductType
     */
    protected $productType;

    /**
     * Retrieve website models
     *
     * @var array
     */
    protected $websites;

    /**
     * @var ProductWebsite
     */
    private $productWebsite;

    /**
     * Product Type Instances cache
     *
     * @var array
     */
    protected $productTypes = [];

    /**
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param ProductRepositoryInterface $productRepository
     * @param ProductWebsite $productWebsite
     * @param ProductType $productType
     */
    public function __construct(
        StockRegistryProviderInterface $stockRegistryProvider,
        ProductRepositoryInterface $productRepository,
        ProductWebsite $productWebsite,
        ProductType $productType
    ) {
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->productRepository = $productRepository;
        $this->productWebsite = $productWebsite;
        $this->productType = $productType;
    }

    /**
     * Rebuild stock index of the given website
     *
     * @param int $productId
     * @param int $scopeId
     * @deprecated 100.1.0
     * @return true
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function rebuild($productId = null, $scopeId = null)
    {
        if ($productId !== null) {
            $this->updateProductStockStatus($productId, $scopeId);
        } else {
            $lastProductId = 0;
            while (true) {
                /** @var StockStatusResourceModel $resource */
                $resource = $this->getStockStatusResource();
                $productCollection = $resource->getProductCollection($lastProductId);
                if (!$productCollection) {
                    break;
                }
                foreach ($productCollection as $productId => $productType) {
                    $lastProductId = $productId;
                    $this->updateProductStockStatus($productId, $scopeId);
                }
            }
        }
        return true;
    }

    /**
     * Update product status from stock item
     *
     * @param int $productId
     * @param int $websiteId
     * @deprecated 100.1.0
     * @return void
     */
    public function updateProductStockStatus($productId, $websiteId)
    {
        $item = $this->stockRegistryProvider->getStockItem($productId, $websiteId);

        $status = Stock\Status::STATUS_IN_STOCK;
        $qty = 0;
        if ($item->getItemId()) {
            $status = $item->getIsInStock();
            $qty = $item->getQty();
        }
        $this->processChildren($productId, $item->getWebsiteId(), $qty, $status);
        $this->processParents($productId, $item->getWebsiteId());
    }

    /**
     * Process children stock status
     *
     * @param int $productId
     * @param int $websiteId
     * @param int $qty
     * @param int $status
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processChildren(
        $productId,
        $websiteId,
        $qty = 0,
        $status = Stock\Status::STATUS_IN_STOCK
    ) {
        if ($status == Stock\Status::STATUS_OUT_OF_STOCK) {
            $this->getStockStatusResource()->saveProductStatus($productId, $status, $qty, $websiteId);
            return;
        }

        $statuses = [];
        $websitesWithStores = $this->getWebsitesWithDefaultStores($websiteId);

        foreach (array_keys($websitesWithStores) as $websiteId) {
            /* @var $website Website */
            $statuses[$websiteId] = $status;
        }

        /** @var Product $product */
        $product = $this->productRepository->getById($productId);
        $typeInstance = $product->getTypeInstance();

        $requiredChildrenIds = $typeInstance->getChildrenIds($productId, true);
        if ($requiredChildrenIds) {
            $childrenIds = [];
            foreach ($requiredChildrenIds as $groupedChildrenIds) {
                $childrenIds[] = $groupedChildrenIds;
            }
            $childrenIds = array_merge([], ...$childrenIds);

            $childrenWebsites = $this->productWebsite->getWebsites($childrenIds);
            foreach ($websitesWithStores as $websiteId => $storeId) {
                $childrenStatus = $this->getStockStatusResource()->getProductStatus($childrenIds, $storeId);
                $childrenStock = $this->getStockStatusResource()->getProductsStockStatuses($childrenIds, $websiteId);
                $websiteStatus = $statuses[$websiteId];
                foreach ($requiredChildrenIds as $groupedChildrenIds) {
                    $optionStatus = false;
                    foreach ($groupedChildrenIds as $childId) {
                        if (isset($childrenStatus[$childId])
                            && isset($childrenWebsites[$childId])
                            && in_array($websiteId, $childrenWebsites[$childId])
                            && $childrenStatus[$childId] == Status::STATUS_ENABLED
                            && isset($childrenStock[$childId])
                            && $childrenStock[$childId] == Stock\Status::STATUS_IN_STOCK
                        ) {
                            $optionStatus = true;
                        }
                    }
                    $websiteStatus = $websiteStatus && $optionStatus;
                }
                $statuses[$websiteId] = (int)$websiteStatus;
            }
        }
        foreach ($statuses as $websiteId => $websiteStatus) {
            $this->getStockStatusResource()->saveProductStatus($productId, $websiteStatus, $qty, $websiteId);
        }
    }

    /**
     * Retrieve website models
     *
     * @param int|null $websiteId
     * @return Website[]
     */
    protected function getWebsitesWithDefaultStores($websiteId = null)
    {
        if ($this->websites === null) {
            /** @var StockStatusResourceModel $resource */
            $resource = $this->getStockStatusResource();
            $this->websites = $resource->getWebsiteStores();
        }
        $websites = $this->websites;
        if ($websiteId !== null && isset($this->websites[$websiteId])) {
            $websites = [$websiteId => $this->websites[$websiteId]];
        }
        return $websites;
    }

    /**
     * Process Parents by child
     *
     * @param int $productId
     * @param int $websiteId
     * @return void
     */
    protected function processParents($productId, $websiteId)
    {
        $parentIds = [];
        foreach ($this->getProductTypeInstances() as $typeInstance) {
            /* @var ProductType\AbstractType $typeInstance */
            $parentIds[] = $typeInstance->getParentIdsByChild($productId);
        }

        $parentIds = array_merge([], ...$parentIds);

        if (empty($parentIds)) {
            return;
        }

        foreach ($parentIds as $parentId) {
            $item = $this->stockRegistryProvider->getStockItem($parentId, $websiteId);
            $status = Stock\Status::STATUS_IN_STOCK;
            $qty = 0;
            if ($item->getItemId()) {
                $status = $item->getIsInStock();
                $qty = $item->getQty();
            }
            $this->processChildren($parentId, $websiteId, $qty, $status);
        }
    }

    /**
     * Retrieve Product Type Instances as key - type code, value - instance model
     *
     * @return ProductType\AbstractType[]
     */
    protected function getProductTypeInstances()
    {
        if (empty($this->productTypes)) {
            $productEmulator = new \Magento\Framework\DataObject();
            foreach (array_keys($this->productType->getTypes()) as $typeId) {
                $productEmulator->setTypeId($typeId);
                $this->productTypes[$typeId] = $this->productType->factory($productEmulator);
            }
        }
        return $this->productTypes;
    }

    /**
     * Returns ResourceModel for Stock Status
     *
     * @return StockStatusResourceModel
     */
    protected function getStockStatusResource()
    {
        if (empty($this->stockStatusResource)) {
            $this->stockStatusResource = ObjectManager::getInstance()->get(StockStatusResourceModel::class);
        }
        return $this->stockStatusResource;
    }
}
