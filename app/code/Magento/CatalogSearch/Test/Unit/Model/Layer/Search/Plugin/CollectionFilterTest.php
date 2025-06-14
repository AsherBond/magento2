<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogSearch\Test\Unit\Model\Layer\Search\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\Search\CollectionFilter;
use Magento\CatalogSearch\Model\Layer\Search\Plugin\CollectionFilter as CollectionFilterPlugin;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionFilterTest extends TestCase
{
    /**
     * @var CollectionFilterPlugin
     */
    private $plugin;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var Category|MockObject
     */
    private $categoryMock;

    /**
     * @var QueryFactory|MockObject
     */
    private $queryFactoryMock;

    /**
     * @var CollectionFilter|MockObject
     */
    private $collectionFilterMock;

    /**
     * @var Query|MockObject
     */
    private $queryMock;

    /***
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addSearchFilter'])
            ->getMock();
        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->queryFactoryMock = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $this->collectionFilterMock = $this->getMockBuilder(CollectionFilter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->queryMock = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isQueryTextShort', 'getQueryText'])
            ->getMock();

        $this->plugin = $this->objectManager->getObject(
            CollectionFilterPlugin::class,
            ['queryFactory' => $this->queryFactoryMock]
        );
    }

    public function testAfterFilter()
    {
        $queryText = 'Test Query';

        $this->queryFactoryMock->expects($this->once())
            ->method('get')
            ->willReturn($this->queryMock);
        $this->queryMock->expects($this->once())
            ->method('isQueryTextShort')
            ->willReturn(false);
        $this->queryMock->expects($this->once())
            ->method('getQueryText')
            ->willReturn($queryText);
        $this->collectionMock->expects($this->once())
            ->method('addSearchFilter')
            ->with($queryText);

        $this->plugin->afterFilter(
            $this->collectionFilterMock,
            null,
            $this->collectionMock,
            $this->categoryMock
        );
    }
}
