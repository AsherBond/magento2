<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\ResourceModel\Product\Indexer\Price;

use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\IndexTableRowSizeEstimator;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Store\Api\WebsiteManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTableRowSizeEstimatorTest extends TestCase
{
    /**
     * @var IndexTableRowSizeEstimator
     */
    private $model;

    /**
     * @var MockObject
     */
    private $websiteManagementMock;

    /**
     * @var MockObject
     */
    private $collectionFactoryMock;

    protected function setUp(): void
    {
        $this->websiteManagementMock = $this->getMockForAbstractClass(WebsiteManagementInterface::class);
        $this->collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->model = new IndexTableRowSizeEstimator(
            $this->websiteManagementMock,
            $this->collectionFactoryMock
        );
    }

    public function testEstimateRowSize()
    {
        $expectedValue = 4000000;

        $this->websiteManagementMock->expects($this->once())->method('getCount')->willReturn(100);
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('getSize')->willReturn(200);

        $this->assertEquals(
            $expectedValue,
            $this->model->estimateRowSize()
        );
    }
}
