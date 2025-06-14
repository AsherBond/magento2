<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewrite\Test\Unit\Observer;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogUrlRewrite\Model\Category\ChildrenCategoriesProvider;
use Magento\CatalogUrlRewrite\Model\CategoryProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Observer\UrlRewriteHandler;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\UrlRewrite\Model\MergeDataProvider;
use Magento\UrlRewrite\Model\MergeDataProviderFactory;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CatalogUrlRewrite\Model\ProductScopeRewriteGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UrlRewriteHandlerTest extends TestCase
{
    /**
     * @var UrlRewriteHandler
     */
    protected $urlRewriteHandler;

    /**
     * @var ChildrenCategoriesProvider|MockObject
     */
    protected $childrenCategoriesProviderMock;

    /**
     * @var CategoryUrlRewriteGenerator|MockObject
     */
    protected $categoryUrlRewriteGeneratorMock;

    /**
     * @var ProductUrlRewriteGenerator|MockObject
     */
    protected $productUrlRewriteGeneratorMock;

    /**
     * @var UrlPersistInterface|MockObject
     */
    protected $urlPersistMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var CategoryProductUrlPathGenerator|MockObject
     */
    private $categoryBasedProductRewriteGeneratorMock;

    /**
     * @var MergeDataProviderFactory|MockObject
     */
    private $mergeDataProviderFactoryMock;

    /**
     * @var MergeDataProvider|MockObject
     */
    private $mergeDataProviderMock;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * @var ProductScopeRewriteGenerator
     */
    private $productScopeRewriteGeneratorMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->childrenCategoriesProviderMock = $this->getMockBuilder(ChildrenCategoriesProvider::class)
            ->getMock();
        $this->categoryUrlRewriteGeneratorMock = $this->getMockBuilder(CategoryUrlRewriteGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productUrlRewriteGeneratorMock = $this->getMockBuilder(ProductUrlRewriteGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlPersistMock = $this->getMockBuilder(UrlPersistInterface::class)
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mergeDataProviderFactoryMock = $this->getMockBuilder(MergeDataProviderFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mergeDataProviderMock = $this->getMockBuilder(MergeDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryBasedProductRewriteGeneratorMock = $this->getMockBuilder(CategoryProductUrlPathGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mergeDataProviderFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->mergeDataProviderMock);
        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productScopeRewriteGeneratorMock = $this->getMockBuilder(ProductScopeRewriteGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlRewriteHandler = new UrlRewriteHandler(
            $this->childrenCategoriesProviderMock,
            $this->categoryUrlRewriteGeneratorMock,
            $this->productUrlRewriteGeneratorMock,
            $this->urlPersistMock,
            $this->collectionFactoryMock,
            $this->categoryBasedProductRewriteGeneratorMock,
            $this->mergeDataProviderFactoryMock,
            $this->serializerMock,
            $this->productScopeRewriteGeneratorMock,
            $this->scopeConfigMock
        );
    }

    /**
     * @test
     */
    public function testGenerateProductUrlRewrites()
    {
        /* @var \Magento\Catalog\Model\Category|MockObject $category */
        $category = $this->getMockBuilder(Category::class)
            ->addMethods(['getChangedProductIds'])
            ->onlyMethods(['getEntityId', 'getStoreId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $category->expects($this->any())
            ->method('getEntityId')
            ->willReturn(2);
        $category->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);
        $category->expects($this->any())
            ->method('getData')
            ->willReturnCallback(function ($arg1) {
                static $callCount = 0;
                $callCount++;
                switch ($callCount) {
                    case 1:
                        if ($arg1 == 'save_rewrites_history') {
                            return true;
                        }
                        break;
                    case 2:
                        if ($arg1 == 'initial_setup_flag') {
                            return null;
                        }
                        break;
                }
            });

        /* @var \Magento\Catalog\Model\Category|MockObject $childCategory1 */
        $childCategory1 = $this->getMockBuilder(Category::class)
            ->onlyMethods(['getEntityId'])
            ->disableOriginalConstructor()
            ->getMock();
        $childCategory1->expects($this->any())
            ->method('getEntityId')
            ->willReturn(100);

        /* @var \Magento\Catalog\Model\Category|MockObject $childCategory1 */
        $childCategory2 = $this->getMockBuilder(Category::class)
            ->onlyMethods(['getEntityId'])
            ->disableOriginalConstructor()
            ->getMock();
        $childCategory1->expects($this->any())
            ->method('getEntityId')
            ->willReturn(200);

        $this->childrenCategoriesProviderMock->expects($this->once())
            ->method('getChildren')
            ->with($category, true)
            ->willReturn([$childCategory1, $childCategory2]);

        /** @var Collection|MockObject $productCollection */
        $productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturnSelf();
        $productCollection->expects($this->any())
            ->method('addIdFilter')
            ->willReturnSelf();
        $productCollection->expects($this->any())->method('setStoreId')->willReturnSelf();
        $productCollection->expects($this->any())->method('addStoreFilter')->willReturnSelf();
        $productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $iterator = new \ArrayIterator([]);
        $productCollection->expects($this->any())->method('getIterator')->willReturn($iterator);

        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($productCollection);

        $this->mergeDataProviderMock->expects($this->any())->method('getData')->willReturn([1, 2]);

        $this->urlRewriteHandler->generateProductUrlRewrites($category);
    }

    public function testDeleteCategoryRewritesForChildren()
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $category->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $this->childrenCategoriesProviderMock->expects($this->once())
            ->method('getChildrenIds')
            ->with($category, true)
            ->willReturn([3, 4]);

        $this->serializerMock->expects($this->exactly(3))
            ->method('serialize');

        $this->urlRewriteHandler->deleteCategoryRewritesForChildren($category);
    }
}
