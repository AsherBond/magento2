<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogInventory\Test\Unit\Block\Stockqty;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Block\Stockqty\DefaultStockqty;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DefaultStockqty
 */
class DefaultStockqtyTest extends TestCase
{
    /**
     * @var DefaultStockqty
     */
    protected $block;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var MockObject
     */
    protected $stockRegistryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->registryMock = $this->createMock(Registry::class);
        $this->stockRegistryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->block = $objectManager->getObject(
            DefaultStockqty::class,
            [
                'registry' => $this->registryMock,
                'stockRegistry' => $this->stockRegistryMock,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->block = null;
    }

    public function testGetIdentities()
    {
        $productTags = ['catalog_product_1'];
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('getIdentities')->willReturn($productTags);
        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);
        $this->assertEquals($productTags, $this->block->getIdentities());
    }

    /**
     * @param int $productStockQty
     * @param int|null $productId
     * @param int|null $websiteId
     * @param int|null $dataQty
     * @param int $expectedQty
     * @dataProvider getStockQtyDataProvider
     */
    public function testGetStockQty($productStockQty, $productId, $websiteId, $dataQty, $expectedQty)
    {
        $this->assertNull($this->block->getData('product_stock_qty'));
        if ($dataQty) {
            $this->setDataArrayValue('product_stock_qty', $dataQty);
        } else {
            $product = $this->createPartialMock(
                Product::class,
                ['getId', 'getStore', '__wakeup']
            );
            $product->expects($this->any())->method('getId')->willReturn($productId);
            $store = $this->createPartialMock(Store::class, ['getWebsiteId', '__wakeup']);
            $store->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);
            $product->expects($this->any())->method('getStore')->willReturn($store);

            $this->registryMock->expects($this->any())
                ->method('registry')
                ->with('current_product')
                ->willReturn($product);

            if ($productId) {
                $stockStatus = $this->getMockBuilder(StockStatusInterface::class)
                    ->getMockForAbstractClass();
                $stockStatus->expects($this->any())->method('getQty')->willReturn($productStockQty);
                $this->stockRegistryMock->expects($this->once())
                    ->method('getStockStatus')
                    ->with($productId, $websiteId)
                    ->willReturn($stockStatus);
            }
        }
        $this->assertSame($expectedQty, $this->block->getStockQty());
        $this->assertSame($expectedQty, $this->block->getData('product_stock_qty'));
    }

    public function testGetStockQtyLeft()
    {
        $productId = 1;
        $minQty = 0;
        $websiteId = 1;
        $stockQty = 2;

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $product->expects($this->any())
            ->method('getId')
            ->willReturn($productId);
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);
        if ($productId) {
            $stockStatus = $this->getMockBuilder(StockStatusInterface::class)
                ->getMockForAbstractClass();
            $stockStatus->expects($this->any())->method('getQty')->willReturn($stockQty);
            $this->stockRegistryMock->expects($this->once())
                ->method('getStockStatus')
                ->with($productId, $websiteId)
                ->willReturn($stockStatus);
        }

        $stockItemMock = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $stockItemMock->expects($this->once())
            ->method('getMinQty')
            ->willReturn($minQty);
        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with($productId)
            ->willReturn($stockItemMock);

        $this->assertEquals($stockQty, $this->block->getStockQtyLeft());
    }

    /**
     * @return array
     */
    public static function getStockQtyDataProvider()
    {
        return [
            [
                'productStockQty' => 100,
                'productId' => 5,
                'websiteId' => 0,
                'dataQty' => null,
                'expectedQty' => 100,
            ],
            [
                'productStockQty' => 100,
                'productId' => null,
                'websiteId' => null,
                'dataQty' => null,
                'expectedQty' => 0
            ],
            [
                'productStockQty' => null,
                'productId' => null,
                'websiteId' => null,
                'dataQty' => 50,
                'expectedQty' => 50
            ],
        ];
    }

    /**
     * @param string $key
     * @param string|float|int $value
     */
    protected function setDataArrayValue($key, $value)
    {
        $property = new \ReflectionProperty($this->block, '_data');
        $property->setAccessible(true);
        $dataArray = $property->getValue($this->block);
        $dataArray[$key] = $value;
        $property->setValue($this->block, $dataArray);
    }

    public function testGetThresholdQty()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn(5);
        $this->assertEquals(5, $this->block->getThresholdQty());
    }
}
