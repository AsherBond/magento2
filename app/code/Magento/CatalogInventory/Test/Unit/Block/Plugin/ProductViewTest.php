<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogInventory\Test\Unit\Block\Plugin;

use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Block\Plugin\ProductView;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductViewTest extends TestCase
{
    /**
     * @var ProductView
     */
    protected $block;

    /**
     * @var StockItemInterface|MockObject
     */
    protected $stockItem;

    /**
     * @var StockRegistryInterface|MockObject
     */
    protected $stockRegistry;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->stockItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMinSaleQty', 'getMaxSaleQty', 'getQtyIncrements'])
            ->getMock();

        $this->stockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->getMock();

        $this->block = $objectManager->getObject(
            ProductView::class,
            [
                'stockRegistry' => $this->stockRegistry
            ]
        );
    }

    public function testAfterGetQuantityValidators()
    {
        $result = [
            'validate-item-quantity' => [
                'maxAllowed' => 5.0,
                'qtyIncrements' => 3.0
            ]
        ];
        $validators = [];
        $productViewBlock = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['_wakeup'])
            ->onlyMethods(['getId', 'getStore'])
            ->getMock();
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->addMethods(['_wakeup'])
            ->onlyMethods(['getWebsiteId'])
            ->getMock();

        $productViewBlock->expects($this->any())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('getId')->willReturn('productId');
        $productMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn('websiteId');
        $this->stockRegistry->expects($this->once())
            ->method('getStockItem')
            ->with('productId', 'websiteId')
            ->willReturn($this->stockItem);
        $this->stockItem->expects($this->any())->method('getMaxSaleQty')->willReturn(5);
        $this->stockItem->expects($this->any())->method('getQtyIncrements')->willReturn(3);

        $this->assertEquals($result, $this->block->afterGetQuantityValidators($productViewBlock, $validators));
    }
}
