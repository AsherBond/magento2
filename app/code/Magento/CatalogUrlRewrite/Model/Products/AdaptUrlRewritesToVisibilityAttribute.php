<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewrite\Model\Products;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 *  Save/Delete UrlRewrites by Product ID's and visibility
 */
class AdaptUrlRewritesToVisibilityAttribute
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductUrlRewriteGenerator
     */
    private $urlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * @var ProductUrlPathGenerator
     */
    private $urlPathGenerator;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductUrlRewriteGenerator $urlRewriteGenerator
     * @param UrlPersistInterface $urlPersist
     * @param ProductUrlPathGenerator|null $urlPathGenerator
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductUrlRewriteGenerator $urlRewriteGenerator,
        UrlPersistInterface $urlPersist,
        ProductUrlPathGenerator $urlPathGenerator
    ) {
        $this->productCollectionFactory = $collectionFactory;
        $this->urlRewriteGenerator = $urlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->urlPathGenerator = $urlPathGenerator;
    }

    /**
     * Process Url Rewrites according to the products visibility attribute
     *
     * @param array $productIds
     * @param int $visibility
     * @param int $storeId
     * @throws UrlAlreadyExistsException
     */
    public function execute(array $productIds, int $visibility, int $storeId): void
    {
        $products = $this->getProductsByIds($productIds, $storeId);

        /** @var Product $product */
        foreach ($products as $product) {
            if ($visibility == Visibility::VISIBILITY_NOT_VISIBLE) {
                $this->urlPersist->deleteByData(
                    [
                        UrlRewrite::ENTITY_ID => $product->getId(),
                        UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
                        UrlRewrite::STORE_ID => $storeId,
                    ]
                );
            } elseif ($visibility !== Visibility::VISIBILITY_NOT_VISIBLE) {
                $product->setVisibility($visibility);
                $productUrlPath = $this->urlPathGenerator->getUrlPath($product);
                $productUrlRewrite = $this->urlRewriteGenerator->generate($product);
                $product->unsUrlPath();
                $product->setUrlPath($productUrlPath);

                try {
                    $this->urlPersist->replace($productUrlRewrite);
                } catch (UrlAlreadyExistsException $e) {
                    throw new UrlAlreadyExistsException(
                        __(
                            'Can not change the visibility of the product with SKU equals "%1". '
                            . 'URL key "%2" for specified store already exists.',
                            $product->getSku(),
                            $product->getUrlKey()
                        ),
                        $e,
                        $e->getCode(),
                        $e->getUrls()
                    );
                }
            }
        }
    }

    /**
     * Get Product Models by Id's
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    private function getProductsByIds(array $productIds, int $storeId): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStoreId($storeId);
        $productCollection->addAttributeToSelect(ProductInterface::VISIBILITY);
        $productCollection->addAttributeToSelect('url_key');
        $productCollection->addFieldToFilter(
            'entity_id',
            ['in' => array_unique($productIds)]
        );

        return $productCollection->getItems();
    }
}
