<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SwatchesLayeredNavigation\Block\Navigation\Search;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\SwatchesLayeredNavigation\Block\Navigation\Category\SwatchVisualFilterTest as CategorySwatchVisualTest;

/**
 * Provides tests for custom visual swatch filter in navigation block on search page.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 */
class SwatchVisualFilterTest extends CategorySwatchVisualTest
{
    /**
     * @magentoDataFixture Magento/Swatches/_files/product_visual_swatch_attribute.php
     * @magentoDataFixture Magento/Catalog/_files/category_with_different_price_products.php
     * @dataProvider getFiltersWithCustomAttributeDataProvider
     * @param array $products
     * @param array $attributeData
     * @param array $expectation
     * @return void
     */
    public function testGetFiltersWithCustomAttribute(
        array $products,
        array $attributeData,
        array $expectation
    ): void {
        $this->getSearchFiltersAndAssert($products, $attributeData, $expectation);
    }

    /**
     * @return array
     */
    public static function getFiltersWithCustomAttributeDataProvider(): array
    {
        $dataProvider = parent::getFiltersWithCustomAttributeDataProvider();

        $dataProvider = array_replace_recursive(
            $dataProvider,
            [
                'not_used_in_navigation' => [
                    'attributeData' => [
                        'is_filterable_in_search' => 0,
                    ],
                ],
                'used_in_navigation_with_results' => [
                    'attributeData' => [
                        'is_filterable' => AbstractFilter::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS,
                        'is_filterable_in_search' => 1,
                    ],
                ],
                'used_in_navigation_without_results' => [
                    'attributeData' => [
                        'is_filterable' => 0,
                        'is_filterable_in_search' => 1,
                    ],
                ],
            ]
        );
        //TODO uncomment after fix MC-29227
        //unset($dataProvider['used_in_navigation_without_results']['expectation'][2]);

        return $dataProvider;
    }

    /**
     * @magentoDataFixture Magento/Swatches/_files/product_visual_swatch_attribute.php
     * @magentoDataFixture Magento/Catalog/_files/category_with_different_price_products.php
     * @dataProvider getActiveFiltersWithCustomAttributeDataProvider
     * @param array $products
     * @param array $expectation
     * @param string $filterValue
     * @param int $productsCount
     * @return void
     */
    public function testGetActiveFiltersWithCustomAttribute(
        array $products,
        array $expectation,
        string $filterValue,
        int $productsCount
    ): void {
        $this->getSearchActiveFiltersAndAssert($products, $expectation, $filterValue, $productsCount);
    }

    /**
     * @inheritdoc
     */
    protected function getLayerType(): string
    {
        return Resolver::CATALOG_LAYER_SEARCH;
    }
}
