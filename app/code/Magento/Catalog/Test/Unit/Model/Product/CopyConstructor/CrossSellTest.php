<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Product\CopyConstructor;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CopyConstructor\CrossSell;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\ResourceModel\Product\Link\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CrossSellTest extends TestCase
{
    /**
     * @var CrossSell
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_productMock;

    /**
     * @var MockObject
     */
    protected $_duplicateMock;

    /**
     * @var MockObject
     */
    protected $_linkMock;

    /**
     * @var MockObject
     */
    protected $_linkCollectionMock;

    protected function setUp(): void
    {
        $this->_model = new CrossSell();

        $this->_productMock = $this->createMock(Product::class);

        $this->_duplicateMock = $this->getMockBuilder(Product::class)
            ->addMethods(['setCrossSellLinkData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_linkMock = $this->getMockBuilder(Link::class)
            ->addMethods(['getCrossSellLinkCollection'])
            ->onlyMethods([ 'getAttributes', 'useCrossSellLinks'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_productMock->expects(
            $this->any()
        )->method(
            'getLinkInstance'
        )->willReturn(
            $this->_linkMock
        );
    }

    public function testBuild()
    {
        $helper = new ObjectManager($this);
        $expectedData = ['100500' => ['some' => 'data']];

        $attributes = ['attributeOne' => ['code' => 'one'], 'attributeTwo' => ['code' => 'two']];

        $this->_linkMock->expects($this->once())->method('useCrossSellLinks');

        $this->_linkMock->expects($this->once())->method('getAttributes')->willReturn($attributes);

        $productLinkMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Link::class)
            ->addMethods(['getLinkedProductId', 'toArray'])
            ->disableOriginalConstructor()
            ->getMock();

        $productLinkMock->expects($this->once())->method('getLinkedProductId')->willReturn('100500');
        $productLinkMock->expects(
            $this->once()
        )->method(
            'toArray'
        )->with(
            ['one', 'two']
        )->willReturn(
            ['some' => 'data']
        );

        $collectionMock = $helper->getCollectionMock(
            Collection::class,
            [$productLinkMock]
        );
        $this->_productMock->expects(
            $this->once()
        )->method(
            'getCrossSellLinkCollection'
        )->willReturn(
            $collectionMock
        );

        $this->_duplicateMock->expects($this->once())->method('setCrossSellLinkData')->with($expectedData);

        $this->_model->build($this->_productMock, $this->_duplicateMock);
    }
}
