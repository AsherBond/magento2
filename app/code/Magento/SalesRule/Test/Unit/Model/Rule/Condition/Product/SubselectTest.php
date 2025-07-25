<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\SalesRule\Test\Unit\Model\Rule\Condition\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Rule\Model\Condition\Context;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\SalesRule\Model\Rule\Condition\Product as SalesRuleProduct;
use Magento\SalesRule\Model\Rule\Condition\Product\Subselect as SalesRuleProductSubselect;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for checking sub select validation
 */
class SubselectTest extends TestCase
{
    /** @var SalesRuleProductSubselect */
    private $model;

    /** @var SalesRuleProduct|MockObject */
    private $ruleConditionMock;

    /** @var MockObject */
    private $abstractModel;

    /** @var Product|MockObject */
    private $productMock;

    /** @var Quote|MockObject */
    private $quoteMock;

    /** @var Item|MockObject */
    private $quoteItemMock;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleConditionMock = $this->getMockBuilder(SalesRuleProduct::class)
            ->onlyMethods(['getAttribute', 'getValueParsed', 'getOperatorForValidate'])
            ->addMethods(['setName', 'setAttributeScope'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->abstractModel = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuote', 'getAllItems', 'getProduct'])
            ->getMockForAbstractClass();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getData', 'getResource', 'hasData'])
            ->addMethods(['getOperatorForValidate', 'getValueParsed'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsMultiShipping'])
            ->onlyMethods(['getAllVisibleItems'])
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['getHasChildren', 'getProductId'])
            ->onlyMethods(
                [
                    'getData',
                    'getProduct',
                    'getProductType',
                    'getChildren',
                    'getQuote',
                    'getAddress',
                    'getOptionByCode'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$this->quoteItemMock]);
        $this->abstractModel->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->abstractModel->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->quoteItemMock]);
        $this->abstractModel->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->model = new SalesRuleProductSubselect(
            $contextMock,
            $this->ruleConditionMock,
            []
        );
    }

    /**
     * Tests validate for fixed bundle product
     *
     * @param array|null $attributeDetails
     * @param array $productDetails
     * @param bool $isMultiShipping
     * @param bool $expectedResult
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider dataProviderForFixedBundleProduct
     */
    public function testValidateForFixedBundleProduct(
        ?array $attributeDetails,
        array $productDetails,
        bool $isMultiShipping,
        bool $expectedResult
    ): void {
        $attributeResource = new DataObject();
        if ($attributeDetails) {
            $attributeResource->setAttribute($attributeDetails['id']);
            $this->ruleConditionMock->expects($this->any())
                ->method('setName')
                ->willReturn($attributeDetails['name']);
            $this->ruleConditionMock->expects($this->any())
                ->method('setAttributeScope')
                ->willReturn($attributeDetails['attributeScope']);
            $this->ruleConditionMock->expects($this->any())
                ->method('getAttribute')
                ->willReturn($attributeDetails['id']);
            $this->model->setData('conditions', [$this->ruleConditionMock]);
            $this->model->setData('attribute', $attributeDetails['id']);
            $this->model->setData('value', $productDetails['valueParsed']);
            $this->model->setData('operator', $attributeDetails['attributeOperator']);
            $this->productMock->expects($this->any())
                ->method('hasData')
                ->with($attributeDetails['id'])
                ->willReturn(!empty($productDetails));
            $this->productMock->expects($this->any())
                ->method('getData')
                ->with($attributeDetails['id'])
                ->willReturn($productDetails['price']);
            $this->ruleConditionMock->expects($this->any())
                ->method('getValueParsed')
                ->willReturn($productDetails['valueParsed']);
            $this->ruleConditionMock->expects($this->any())->method('getOperatorForValidate')
                ->willReturn($attributeDetails['attributeOperator']);
        }
        $this->quoteMock->expects($this->any())
            ->method('getIsMultiShipping')
            ->willReturn($isMultiShipping);
        $this->quoteItemMock->expects($this->any())
            ->method('getProductType')
            ->willReturn($productDetails['type']);

        /* @var AbstractItem|MockObject $quoteItemMock */
        $childQuoteItemMock = $this->getMockBuilder(AbstractItem::class)
            ->onlyMethods(['getProduct', 'getData', 'getPrice', 'getQty'])
            ->addMethods(['getBaseRowTotal'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $childQuoteItemMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $childQuoteItemMock->expects($this->any())
            ->method('getQty')
            ->willReturn($productDetails['qty']);
        $childQuoteItemMock->expects($this->any())
            ->method('getPrice')
            ->willReturn($productDetails['price']);
        $childQuoteItemMock->expects($this->any())
            ->method('getBaseRowTotal')
            ->willReturn($productDetails['baseRowTotal']);
        $this->productMock->expects($this->any())
            ->method('getResource')
            ->willReturn($attributeResource);
        $this->quoteItemMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->quoteItemMock->expects($this->any())
            ->method('getHasChildren')
            ->willReturn($productDetails['hasChildren']);
        $this->quoteItemMock->expects($this->any())
            ->method('getChildren')
            ->willReturn([$childQuoteItemMock]);
        $this->quoteItemMock->expects($this->any())
            ->method('getProductId')
            ->willReturn($productDetails['id']);
        $this->quoteItemMock->expects($this->any())
            ->method('getChildren')
            ->willReturn([$childQuoteItemMock]);
        $this->quoteItemMock->expects($this->any())
            ->method('getData')
            ->willReturn($productDetails['baseRowTotal']);
        $this->assertEquals($expectedResult, $this->model->validate($this->abstractModel));
    }

    /**
     * Tests validate for fixed bundle product
     *
     * @param array|null $attributeDetails
     * @param array $productDetails
     * @param bool $isMultiShipping
     * @param bool $expectedResult
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider dataProviderForBundleAndSimpleProducts
     */
    public function testValidateForBundleAndSimpleProducts(
        array $attributeDetails,
        array $productsDetails,
        bool $isMultiShipping,
        bool $expectedResult
    ): void {
        $attributeResource = new DataObject();
        $childrenQuoteItemsMock = [];
        foreach ($productsDetails as $productDetails) {
            $attributeResource->setAttribute($attributeDetails['id']);
            $this->ruleConditionMock->expects($this->any())
                ->method('setName')
                ->willReturn($attributeDetails['name']);
            $this->ruleConditionMock->expects($this->any())
                ->method('setAttributeScope')
                ->willReturn($attributeDetails['attributeScope']);
            $this->ruleConditionMock->expects($this->any())
                ->method('getAttribute')
                ->willReturn($attributeDetails['id']);
            $this->model->setData('conditions', [$this->ruleConditionMock]);
            $this->model->setData('attribute', $attributeDetails['id']);
            $this->model->setData('value', $productDetails['valueParsed']);
            $this->model->setData('operator', $attributeDetails['attributeOperator']);
            $this->productMock->expects($this->any())
                ->method('hasData')
                ->with($attributeDetails['id'])
                ->willReturn(!empty($productDetails));
            $this->productMock->expects($this->any())
                ->method('getData')
                ->with($attributeDetails['id'])
                ->willReturn($productDetails['price']);
            $this->ruleConditionMock->expects($this->any())
                ->method('getValueParsed')
                ->willReturn($productDetails['valueParsed']);
            $this->ruleConditionMock->expects($this->any())->method('getOperatorForValidate')
                ->willReturn($attributeDetails['attributeOperator']);

            $this->quoteMock->expects($this->any())
                ->method('getIsMultiShipping')
                ->willReturn($isMultiShipping);
            $this->quoteItemMock->expects($this->any())
                ->method('getProductType')
                ->willReturn($productDetails['type']);

            /* @var AbstractItem|MockObject $quoteItemMock */
            $childQuoteItemMock = $this->getMockBuilder(AbstractItem::class)
                ->onlyMethods(['getProduct', 'getData', 'getPrice', 'getQty'])
                ->addMethods(['getBaseRowTotal'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $childQuoteItemMock->expects($this->any())
                ->method('getProduct')
                ->willReturn($this->productMock);
            $childQuoteItemMock->expects($this->any())
                ->method('getQty')
                ->willReturn($productDetails['qty']);
            $childQuoteItemMock->expects($this->any())
                ->method('getPrice')
                ->willReturn($productDetails['price']);
            $childQuoteItemMock->expects($this->any())
                ->method('getBaseRowTotal')
                ->willReturn($productDetails['baseRowTotal']);
            $this->productMock->expects($this->any())
                ->method('getResource')
                ->willReturn($attributeResource);
            $this->quoteItemMock->expects($this->any())
                ->method('getProduct')
                ->willReturn($this->productMock);
            $this->quoteItemMock->expects($this->any())
                ->method('getHasChildren')
                ->willReturn($productDetails['hasChildren']);
            $this->quoteItemMock->expects($this->any())
                ->method('getChildren')
                ->willReturn([$childQuoteItemMock]);
            $this->quoteItemMock->expects($this->any())
                ->method('getProductId')
                ->willReturn($productDetails['id']);
            $this->quoteItemMock->expects($this->any())
                ->method('getChildren')
                ->willReturn([$childQuoteItemMock]);
            $this->quoteItemMock->expects($this->any())
                ->method('getData')
                ->willReturn($productDetails['baseRowTotal']);
            $childrenQuoteItemsMock[] = clone $this->quoteItemMock;
        }

        $abstractModel = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuote', 'getAllItems', 'getProduct'])
            ->getMockForAbstractClass();
        $abstractModel->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteMock);
        $abstractModel->expects($this->any())
            ->method('getAllItems')
            ->willReturn($childrenQuoteItemsMock);
        $this->assertEquals($expectedResult, $this->model->validate($abstractModel));
    }

    /**
     * Get data provider array for validate bundle and simple products
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function dataProviderForBundleAndSimpleProducts(): array
    {
        return [
            'validate true for multiple products  with conditions
            for attribute base_row_total with multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'test conditions',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        [
                            'id'=> 1,
                            'type' => ProductType::TYPE_SIMPLE,
                            'qty' => 1,
                            'price' => 100,
                            'hasChildren' => false ,
                            'baseRowTotal' => 100,
                            'valueParsed' => 100
                        ],
                        [
                            'id'=> 1,
                            'type' => ProductType::TYPE_BUNDLE,
                            'qty' => 1,
                            'price' => 100,
                            'hasChildren' => true,
                            'baseRowTotal' => 100,
                            'valueParsed' => 100
                        ],
                    ],
                    true,
                    true
                ],
        ];
    }

    /**
     * Get data provider array for validate bundle product
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function dataProviderForFixedBundleProduct(): array
    {
        return [
            'validate true for bundle product data with conditions with multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'test conditions',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_BUNDLE,
                        'qty' => 1,
                        'price' => 100,
                        'hasChildren' => true,
                        'baseRowTotal' => 100,
                        'valueParsed' => 100
                    ],
                    true,
                    true
                ],
            'validate false for bundle product data with conditions w/o multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'test conditions',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_BUNDLE,
                        'qty' => 1,
                        'price' => 100,
                        'hasChildren' => true ,
                        'baseRowTotal' => 100,
                        'valueParsed' => 50
                    ],
                    false,
                    false
                ],
            'validate product data without conditions with bundle product w/o multi shipping' =>
                [
                    null,
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_BUNDLE,
                        'qty' => 1,
                        'price' => 100,
                        'hasChildren' => true ,
                        'baseRowTotal' => 100,
                        'valueParsed' => 100
                    ],
                    false,
                    false
                ],
            'validate true for bundle product
            data with conditions for attribute base_row_total w/o multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'base_row_total',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_BUNDLE,
                        'qty' => 2,
                        'price' => 100,
                        'hasChildren' => true,
                        'baseRowTotal' => 200,
                        'valueParsed' => 200
                    ],
                    false,
                    false
                ],
            'validate true for simple product data with conditions with multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'test conditions',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_SIMPLE,
                        'qty' => 1,
                        'price' => 100,
                        'hasChildren' => false ,
                        'baseRowTotal' => 100,
                        'valueParsed' => 100
                    ],
                    true,
                    true
                ],
            'validate false for simple product data with conditions w/o multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'test conditions',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_SIMPLE,
                        'qty' => 1,
                        'price' => 100,
                        'hasChildren' => false,
                        'baseRowTotal' => 100,
                        'valueParsed' => 50
                    ],
                    false,
                    false
                ]
        ];
    }

    /**
     * Tests validate for base row total incl tax
     *
     * @param array|null $attributeDetails
     * @param array $productDetails
     * @param bool $isMultiShipping
     * @param bool $expectedResult
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider dataProviderForBaseTotalInclTax
     */
    public function testValidateForBaseTotalInclTax(
        ?array $attributeDetails,
        array $productDetails,
        bool $isMultiShipping,
        bool $expectedResult
    ):void {
        $attributeResource = new DataObject();
        if ($attributeDetails) {
            $attributeResource->setAttribute($attributeDetails['id']);
            $this->ruleConditionMock->expects($this->any())
                ->method('setName')
                ->willReturn($attributeDetails['name']);
            $this->ruleConditionMock->expects($this->any())
                ->method('setAttributeScope')
                ->willReturn($attributeDetails['attributeScope']);
            $this->ruleConditionMock->expects($this->any())
                ->method('getAttribute')
                ->willReturn($attributeDetails['id']);
            $this->model->setData('conditions', [$this->ruleConditionMock]);
            $this->model->setData('attribute', $attributeDetails['id']);
            $this->model->setData('value', $productDetails['valueParsed']);
            $this->model->setData('operator', $attributeDetails['attributeOperator']);
            $this->productMock->expects($this->any())
                ->method('hasData')
                ->with($attributeDetails['id'])
                ->willReturn(!empty($productDetails));
            $this->productMock->expects($this->any())
                ->method('getData')
                ->with($attributeDetails['id'])
                ->willReturn($productDetails['price']);
            $this->ruleConditionMock->expects($this->any())
                ->method('getValueParsed')
                ->willReturn($productDetails['valueParsed']);
            $this->ruleConditionMock->expects($this->any())->method('getOperatorForValidate')
                ->willReturn($attributeDetails['attributeOperator']);
        }

        $this->quoteMock->expects($this->any())
            ->method('getIsMultiShipping')
            ->willReturn($isMultiShipping);
        /* @var AbstractItem|MockObject $quoteItemMock */
        $this->productMock->expects($this->any())
            ->method('getResource')
            ->willReturn($attributeResource);
        $this->quoteItemMock->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->quoteItemMock->expects($this->any())
            ->method('getProductId')
            ->willReturn($productDetails['id']);
        $this->quoteItemMock->expects($this->any())
            ->method('getData')
            ->willReturn($productDetails['baseRowTotalInclTax']);
        $this->assertEquals($expectedResult, $this->model->validate($this->abstractModel));
    }

    /**
     * Get data provider array for validate base total incl tax
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function dataProviderForBaseTotalInclTax(): array
    {
        return [
            'validate true for product data with conditions
            for attribute base_row_total_incl_tax w/o multi shipping' =>
                [
                    [
                        'id' => 'attribute_set_id',
                        'name' => 'base_row_total_incl_tax',
                        'attributeScope' => 'frontend',
                        'attributeOperator' => '=='
                    ],
                    [
                        'id'=> 1,
                        'type' => ProductType::TYPE_SIMPLE,
                        'qty' => 2,
                        'price' => 100,
                        'hasChildren' => true,
                        'baseRowTotalInclTax' => 200,
                        'valueParsed' => 200
                    ],
                    false,
                    false
                ]
        ];
    }
}
