<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\ConditionProcessor\ConditionBuilder;

use Magento\Catalog\Model\Api\SearchCriteria\CollectionProcessor\ConditionProcessor\ConditionBuilder\Factory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\ConditionProcessor\CustomConditionInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    /**
     * @var ProductResource|MockObject
     */
    private $productResourceMock;

    /**
     * @var EavConfig|MockObject
     */
    private $eavConfigMock;

    /**
     * @var CustomConditionInterface|MockObject
     */
    private $eavAttrConditionBuilderMock;

    /**
     * @var CustomConditionInterface|MockObject
     */
    private $nativeAttrConditionBuilderMock;

    /**
     * @var Factory
     */
    private $conditionBuilderFactory;

    protected function setUp(): void
    {
        $this->productResourceMock = $this->getMockBuilder(ProductResource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityTable'])
            ->getMock();

        $this->eavConfigMock = $this->getMockBuilder(EavConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $this->eavAttrConditionBuilderMock = $this->getMockBuilder(CustomConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->nativeAttrConditionBuilderMock = $this->getMockBuilder(CustomConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->conditionBuilderFactory = $objectManagerHelper->getObject(
            Factory::class,
            [
                'eavConfig' => $this->eavConfigMock,
                'productResource' => $this->productResourceMock,
                'eavAttributeConditionBuilder' => $this->eavAttrConditionBuilderMock,
                'nativeAttributeConditionBuilder' => $this->nativeAttrConditionBuilderMock,
            ]
        );
    }

    public function testNativeAttrConditionBuilder()
    {
        $fieldName = 'super_field';
        $attributeTable = 'my-table';
        $productResourceTable = 'my-table';

        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getField'])
            ->getMock();

        $filterMock
            ->method('getField')
            ->willReturn($fieldName);

        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBackendTable'])
            ->getMock();

        $this->eavConfigMock
            ->method('getAttribute')
            ->with(Product::ENTITY, $fieldName)
            ->willReturn($attributeMock);

        $attributeMock
            ->method('getBackendTable')
            ->willReturn($attributeTable);

        $this->productResourceMock
            ->method('getEntityTable')
            ->willReturn($productResourceTable);

        $this->assertEquals(
            $this->nativeAttrConditionBuilderMock,
            $this->conditionBuilderFactory->createByFilter($filterMock)
        );
    }

    public function testEavAttrConditionBuilder()
    {
        $fieldName = 'super_field';
        $attributeTable = 'my-table';
        $productResourceTable = 'not-my-table';

        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getField'])
            ->getMock();

        $filterMock
            ->method('getField')
            ->willReturn($fieldName);

        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBackendTable'])
            ->getMock();

        $this->eavConfigMock
            ->method('getAttribute')
            ->with(Product::ENTITY, $fieldName)
            ->willReturn($attributeMock);

        $attributeMock
            ->method('getBackendTable')
            ->willReturn($attributeTable);

        $this->productResourceMock
            ->method('getEntityTable')
            ->willReturn($productResourceTable);

        $this->assertEquals(
            $this->eavAttrConditionBuilderMock,
            $this->conditionBuilderFactory->createByFilter($filterMock)
        );
    }
}
