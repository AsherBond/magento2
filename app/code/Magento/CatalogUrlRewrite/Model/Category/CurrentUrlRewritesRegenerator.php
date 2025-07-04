<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\CatalogUrlRewrite\Model\Category;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\UrlRewrite\Model\OptionProvider;

class CurrentUrlRewritesRegenerator
{
    /**
     * @var \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory
     */
    protected $urlRewriteFactory;

    /**
     * @var \Magento\UrlRewrite\Service\V1\Data\UrlRewrite
     */
    private $urlRewritePrototype;

    /**
     * @var \Magento\Catalog\Model\Category
     * @deprecated 100.1.0
     */
    protected $category;

    /**
     * @var \Magento\UrlRewrite\Model\UrlFinderInterface
     * @deprecated 100.1.0
     */
    protected $urlFinder;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\Map\UrlRewriteFinder
     */
    private $urlRewriteFinder;

    /**
     * @var \Magento\UrlRewrite\Model\MergeDataProvider
     */
    private $mergeDataProviderPrototype;

    /**
     * @param \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\UrlFinderInterface $urlFinder
     * @param \Magento\CatalogUrlRewrite\Model\Map\UrlRewriteFinder|null $urlRewriteFinder
     * @param \Magento\UrlRewrite\Model\MergeDataProviderFactory|null $mergeDataProviderFactory
     */
    public function __construct(
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\UrlFinderInterface $urlFinder,
        ?\Magento\CatalogUrlRewrite\Model\Map\UrlRewriteFinder $urlRewriteFinder = null,
        ?\Magento\UrlRewrite\Model\MergeDataProviderFactory $mergeDataProviderFactory = null
    ) {
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->urlRewritePrototype = $urlRewriteFactory->create();
        $this->urlFinder = $urlFinder;
        $this->urlRewriteFinder = $urlRewriteFinder ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\CatalogUrlRewrite\Model\Map\UrlRewriteFinder::class);
        if (!isset($mergeDataProviderFactory)) {
            $mergeDataProviderFactory =  \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\UrlRewrite\Model\MergeDataProviderFactory::class
            );
        }
        $this->mergeDataProviderPrototype = $mergeDataProviderFactory->create();
    }

    /**
     * Generate list based on current url rewrites
     *
     * @param int $storeId
     * @param \Magento\Catalog\Model\Category $category
     * @param int|null $rootCategoryId
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    public function generate($storeId, \Magento\Catalog\Model\Category $category, $rootCategoryId = null)
    {
        $mergeDataProvider = clone $this->mergeDataProviderPrototype;
        $currentUrlRewrites = $this->urlRewriteFinder->findAllByData(
            $category->getEntityId(),
            $storeId,
            CategoryUrlRewriteGenerator::ENTITY_TYPE,
            $rootCategoryId
        );

        foreach ($currentUrlRewrites as $rewrite) {
            $mergeDataProvider->merge(
                $rewrite->getIsAutogenerated()
                ? $this->generateForAutogenerated($rewrite, $storeId, $category)
                : $this->generateForCustom($rewrite, $storeId, $category)
            );
        }

        return $mergeDataProvider->getData();
    }

    /**
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewrite $url
     * @param int $storeId
     * @param \Magento\Catalog\Model\Category|null $category
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    protected function generateForAutogenerated($url, $storeId, ?\Magento\Catalog\Model\Category $category = null)
    {
        if ($category->getData('save_rewrites_history')) {
            $targetPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId);
            if ($url->getRequestPath() !== $targetPath) {
                $generatedUrl = clone $this->urlRewritePrototype;
                $generatedUrl->setEntityType(CategoryUrlRewriteGenerator::ENTITY_TYPE)
                    ->setEntityId($category->getEntityId())
                    ->setRequestPath($url->getRequestPath())
                    ->setTargetPath($targetPath)
                    ->setRedirectType(OptionProvider::PERMANENT)
                    ->setStoreId($storeId)
                    ->setIsAutogenerated(0);
                return [$generatedUrl];
            }
        }
        return [];
    }

    /**
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewrite $url
     * @param int $storeId
     * @param \Magento\Catalog\Model\Category|null $category
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    protected function generateForCustom($url, $storeId, ?\Magento\Catalog\Model\Category $category = null)
    {
        $targetPath = !$url->getRedirectType()
            ? $url->getTargetPath()
            : $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId);
        if ($url->getRequestPath() !== $targetPath) {
            $generatedUrl = clone $this->urlRewritePrototype;
            $generatedUrl->setEntityType(CategoryUrlRewriteGenerator::ENTITY_TYPE)
                ->setEntityId($category->getEntityId())
                ->setRequestPath($url->getRequestPath())
                ->setTargetPath($targetPath)
                ->setRedirectType($url->getRedirectType())
                ->setStoreId($storeId)
                ->setDescription($url->getDescription())
                ->setIsAutogenerated(0)
                ->setMetadata($url->getMetadata());
            return [$generatedUrl];
        }
        return [];
    }
}
