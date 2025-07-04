<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogInventory\Test\Unit\Model\Spi;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\StockStateProvider;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Math\Division;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CatalogInventory\Model\StockStateProvider class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockStateProviderTest extends TestCase
{
    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /**
     * @var StockStateProviderInterface
     */
    protected $stockStateProvider;

    /**
     * @var ProductFactory|MockObject
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\Product|MockObject
     */
    protected $product;

    /**
     * @var Division|MockObject
     */
    protected $mathDivision;

    /**
     * @var FormatInterface|MockObject
     */
    protected $localeFormat;

    /**
     * @var Factory|MockObject
     */
    protected $objectFactory;

    /**
     * @var DataObject|MockObject
     */
    protected $object;

    /**
     * @var float
     */
    protected $qty = 50.5;

    /**
     * @var bool
     */
    protected $qtyCheckApplicable = true;

    /**
     * @var array
     */
    protected $stockAddItemMethods = [
        'getId',
        'getWebsiteId',
        'hasStockQty',
        'setStockQty',
        'getData',
        'getSuppressCheckQtyIncrements',
        'getIsChildItem',
        'getIsSaleable',
        'getOrderedItems',
        'setOrderedItems',
        'getProductName'
    ];

    /**
     * @var array
     */
    protected $stockItemMethods = [
        'getProductId',
        'getStockId',
        'getQty',
        'getIsInStock',
        'getIsQtyDecimal',
        'getShowDefaultNotificationMessage',
        'getUseConfigMinQty',
        'getMinQty',
        'getUseConfigMinSaleQty',
        'getMinSaleQty',
        'getUseConfigMaxSaleQty',
        'getMaxSaleQty',
        'getUseConfigBackorders',
        'getBackorders',
        'getUseConfigNotifyStockQty',
        'getNotifyStockQty',
        'getUseConfigQtyIncrements',
        'getQtyIncrements',
        'getUseConfigEnableQtyInc',
        'getEnableQtyIncrements',
        'getUseConfigManageStock',
        'getManageStock',
        'getLowStockDate',
        'getIsDecimalDivided',
        'getStockStatusChangedAuto',
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->mathDivision = $this->createPartialMock(Division::class, ['getExactDivision']);

        $this->localeFormat = $this->getMockForAbstractClass(
            FormatInterface::class
        );
        $this->localeFormat->expects($this->any())
            ->method('getNumber')
            ->willReturn($this->qty);

        $this->object = $this->objectManagerHelper->getObject(DataObject::class);
        $this->objectFactory = $this->createPartialMock(Factory::class, ['create']);
        $this->objectFactory->expects($this->any())->method('create')->willReturn($this->object);

        $this->product = $this->createPartialMock(
            Product::class,
            ['load', 'isComposite', '__wakeup', 'isSaleable']
        );
        $this->productFactory = $this->createPartialMock(ProductFactory::class, ['create']);
        $this->productFactory->expects($this->any())->method('create')->willReturn($this->product);

        $this->stockStateProvider = $this->objectManagerHelper->getObject(
            StockStateProvider::class,
            [
                'mathDivision' => $this->mathDivision,
                'localeFormat' => $this->localeFormat,
                'objectFactory' => $this->objectFactory,
                'productFactory' => $this->productFactory,
                'qtyCheckApplicable' => $this->qtyCheckApplicable
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->stockStateProvider = null;
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider verifyStockDataProvider
     */
    public function testVerifyStock(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->verifyStock($stockItem)
        );
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider verifyNotificationDataProvider
     */
    public function testVerifyNotification(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->verifyNotification($stockItem)
        );
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider checkQtyDataProvider
     */
    public function testCheckQty(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->checkQty($stockItem, $this->qty)
        );
    }

    /**
     * Check quantity with out-of-stock status but positive or 0 quantity.
     *
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider checkQtyWithStockStatusDataProvider
     */
    public function testCheckQtyWithPositiveQtyAndOutOfStockstatus(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->checkQty($stockItem, $this->qty)
        );
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider suggestQtyDataProvider
     */
    public function testSuggestQty(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->suggestQty($stockItem, $this->qty)
        );
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider getStockQtyDataProvider
     */
    public function testGetStockQty(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->getStockQty($stockItem)
        );
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider checkQtyIncrementsDataProvider
     */
    public function testCheckQtyIncrements(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->checkQtyIncrements($stockItem, $this->qty)->getHasError()
        );
    }

    /**
     * @param \Closure $stockItem
     * @param mixed $expectedResult
     * @dataProvider checkQuoteItemQtyDataProvider
     */
    public function testCheckQuoteItemQty(\Closure $stockItem, $expectedResult)
    {
        $stockItem = $stockItem($this);
        $this->assertEquals(
            $expectedResult,
            $this->stockStateProvider->checkQuoteItemQty(
                $stockItem,
                $this->qty,
                $this->qty,
                $this->qty
            )->getHasError()
        );
    }

    /**
     * @return array
     */
    public static function verifyStockDataProvider()
    {
        return self::prepareDataForMethod('verifyStock');
    }

    /**
     * @return array
     */
    public static function verifyNotificationDataProvider()
    {
        return self::prepareDataForMethod('verifyNotification');
    }

    /**
     * @return array
     */
    public static function checkQtyDataProvider()
    {
        return self::prepareDataForMethod('checkQty');
    }

    /**
     * @return array
     */
    public static function checkQtyWithStockStatusDataProvider()
    {
        return self::prepareDataForMethod('checkQty', self::getVariationsForQtyAndStock());
    }

    /**
     * @return array
     */
    public static function suggestQtyDataProvider()
    {
        return self::prepareDataForMethod('suggestQty');
    }

    /**
     * @return array
     */
    public static function getStockQtyDataProvider()
    {
        return self::prepareDataForMethod('getStockQty');
    }

    /**
     * @return array
     */
    public static function checkQtyIncrementsDataProvider()
    {
        return self::prepareDataForMethod('checkQtyIncrements');
    }

    /**
     * @return array
     */
    public static function checkQuoteItemQtyDataProvider()
    {
        return self::prepareDataForMethod('checkQuoteItemQty');
    }

    protected function getStockItemClassMock($variation)
    {
        $stockItem = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods($this->stockAddItemMethods)
            ->onlyMethods($this->stockItemMethods)
            ->getMockForAbstractClass();
        $stockItem->expects($this->any())->method('getSuppressCheckQtyIncrements')->willReturn(
            $variation['values']['_suppress_check_qty_increments_']
        );
        $stockItem->expects($this->any())->method('getIsSaleable')->willReturn(
            $variation['values']['_is_saleable_']
        );
        $stockItem->expects($this->any())->method('getOrderedItems')->willReturn(
            $variation['values']['_ordered_items_']
        );

        $stockItem->expects($this->any())->method('getProductName')->willReturn($variation['values']['_product_']);
        $stockItem->expects($this->any())->method('getIsChildItem')->willReturn(false);
        $stockItem->expects($this->any())->method('hasStockQty')->willReturn(false);
        $stockItem->expects($this->any())->method('setStockQty')->willReturnSelf();
        $stockItem->expects($this->any())->method('setOrderedItems')->willReturnSelf();
        $stockItem->expects($this->any())->method('getData')
            ->with('stock_qty')
            ->willReturn($variation['values']['_stock_qty_']);

        foreach ($this->stockItemMethods as $method) {
            $value = isset($variation['values'][$method]) ? $variation['values'][$method] : null;
            $stockItem->expects($this->any())->method($method)->willReturn($value);
        }

        return $stockItem;
    }

    /**
     * @param $methodName
     * @param array|null $options
     * @return array
     */
    protected static function prepareDataForMethod($methodName, ?array $options = null)
    {
        $variations = [];
        if ($options === null) {
            $options = self::getVariations();
        }
        foreach ($options as $variation) {
            $stockItem = static fn (self $testCase) => $testCase->getStockItemClassMock($variation);
            $expectedResult = isset($variation['results'][$methodName]) ? $variation['results'][$methodName] : null;
            $variations[] = [
                'stockItem' => $stockItem,
                'expectedResult' => $expectedResult,
            ];
        }
        return $variations;
    }

    /**
     * @return array
     */
    private static function getVariations()
    {
        $stockQty = 100;
        return [
            [
                'values' => [
                    'getIsInStock' => true,
                    'getQty' => $stockQty,
                    'getMinQty' => 0,
                    'getMinSaleQty' => 0,
                    'getMaxSaleQty' => 99,
                    'getNotifyStockQty' => 10,
                    'getManageStock' => true,
                    'getBackorders' => 1,
                    'getQtyIncrements' => 3,
                    '_stock_qty_' => $stockQty,
                    '_suppress_check_qty_increments_' => false,
                    '_is_saleable_' => true,
                    '_ordered_items_' => 0,
                    '_product_' => 'Test product Name',
                ],
                'results' => [
                    'verifyStock' => true,
                    'verifyNotification' => false,
                    'checkQty' => true,
                    'suggestQty' => 51,
                    'getStockQty' => $stockQty,
                    'checkQtyIncrements' => false,
                    'checkQuoteItemQty' => true,
                ],
            ],
            [
                'values' => [
                    'getIsInStock' => true,
                    'getQty' => $stockQty,
                    'getMinQty' => 60,
                    'getMinSaleQty' => 0,
                    'getMaxSaleQty' => 99,
                    'getNotifyStockQty' => 101,
                    'getManageStock' => true,
                    'getBackorders' => 3,
                    'getQtyIncrements' => 1,
                    '_stock_qty_' => $stockQty,
                    '_suppress_check_qty_increments_' => false,
                    '_is_saleable_' => true,
                    '_ordered_items_' => 0,
                    '_product_' => 'Test product Name',
                ],
                'results' => [
                    'verifyStock' => true,
                    'verifyNotification' => true,
                    'checkQty' => false,
                    'suggestQty' => 50.5,
                    'getStockQty' => $stockQty,
                    'checkQtyIncrements' => false,
                    'checkQuoteItemQty' => true,
                ],
            ],
            [
                'values' => [
                    'getIsInStock' => true,
                    'getQty' => null,
                    'getMinQty' => 60,
                    'getMinSaleQty' => 1,
                    'getMaxSaleQty' => 99,
                    'getNotifyStockQty' => 101,
                    'getManageStock' => true,
                    'getBackorders' => 0,
                    'getQtyIncrements' => 1,
                    '_stock_qty_' => null,
                    '_suppress_check_qty_increments_' => false,
                    '_is_saleable_' => true,
                    '_ordered_items_' => 0,
                    '_product_' => 'Test product Name',
                ],
                'results' => [
                    'verifyStock' => false,
                    'verifyNotification' => true,
                    'checkQty' => false,
                    'suggestQty' => 50.5,
                    'getStockQty' => null,
                    'checkQtyIncrements' => false,
                    'checkQuoteItemQty' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private static function getVariationsForQtyAndStock()
    {
        $stockQty = 100;
        return [
            [
                'values' => [
                    'getIsInStock' => false,
                    'getQty' => $stockQty,
                    'getMinQty' => 60,
                    'getMinSaleQty' => 1,
                    'getMaxSaleQty' => 99,
                    'getNotifyStockQty' => 101,
                    'getManageStock' => true,
                    'getBackorders' => 0,
                    'getQtyIncrements' => 1,
                    '_stock_qty_' => null,
                    '_suppress_check_qty_increments_' => false,
                    '_is_saleable_' => true,
                    '_ordered_items_' => 0,
                    '_product_' => 'Test product Name',
                ],
                'results' => [
                    'checkQty' => false
                ]
            ],
            [
                'values' => [
                    'getIsInStock' => false,
                    'getQty' => 0,
                    'getMinQty' => 60,
                    'getMinSaleQty' => 1,
                    'getMaxSaleQty' => 99,
                    'getNotifyStockQty' => 101,
                    'getManageStock' => true,
                    'getBackorders' => 0,
                    'getQtyIncrements' => 1,
                    '_stock_qty_' => null,
                    '_suppress_check_qty_increments_' => false,
                    '_is_saleable_' => true,
                    '_ordered_items_' => 0,
                    '_product_' => 'Test product Name',
                ],
                'results' => [
                    'checkQty' => false
                ]
            ]
        ];
    }

    /**
     * @param bool $isChildItem
     * @param string $expectedMsg
     * @dataProvider checkQtyIncrementsMsgDataProvider
     */
    public function testCheckQtyIncrementsMsg($isChildItem, $expectedMsg)
    {
        $qty = 1;
        $qtyIncrements = 5;
        $stockItem = $this->getMockBuilder(StockItemInterface::class)
            ->addMethods($this->stockAddItemMethods)
            ->onlyMethods($this->stockItemMethods)
            ->getMockForAbstractClass();
        $stockItem->expects($this->any())->method('getSuppressCheckQtyIncrements')->willReturn(false);
        $stockItem->expects($this->any())->method('getQtyIncrements')->willReturn($qtyIncrements);
        $stockItem->expects($this->any())->method('getIsChildItem')->willReturn($isChildItem);
        $stockItem->expects($this->any())->method('getProductName')->willReturn('Simple Product');
        $this->mathDivision->expects($this->any())->method('getExactDivision')->willReturn(1);

        $result = $this->stockStateProvider->checkQtyIncrements($stockItem, $qty);
        $this->assertTrue($result->getHasError());
        $this->assertEquals($expectedMsg, $result->getMessage()->render());
    }

    /**
     * @return array
     */
    public static function checkQtyIncrementsMsgDataProvider()
    {
        return [
            [true, 'You can buy Simple Product only in quantities of 5 at a time.'],
            [false, 'You can buy this product only in quantities of 5 at a time.'],
        ];
    }
}
